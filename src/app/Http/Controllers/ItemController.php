<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Category;
use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\PurchaseRequest;
use App\Models\Comment;
use App\Models\ItemLike;
use App\Models\Order;
use Illuminate\Support\Facades\DB;


class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('item_names', 'like', "%{$keyword}%");
        }

        $activeTab = $request->query('tab', 'recommend');

        // タブ=マイリストの場合は、ログインユーザーが「いいね」した商品のみ表示
        if ($activeTab === 'mylist') {
            if (Auth::check()) {
                $userId = Auth::id();
                $query->whereExists(function ($sub) use ($userId) {
                    $sub->selectRaw('1')
                        ->from('item_likes')
                        ->whereColumn('item_likes.item_id', 'items.id')
                        ->where('item_likes.user_id', $userId);
                })
                // ユーザー自身が出品した商品はマイリストに表示しない
                ->where('user_id', '!=', $userId);
            } else {
                // 未ログイン時は空にする
                $query->whereRaw('1=0');
            }
        } else {
            // おすすめタブなど通常一覧では自分の出品を除外
            if (Auth::check()) {
                $query->where('user_id', '!=', Auth::id());
            }
        }

        $items = $query
            ->with(['user', 'categories'])
            ->latest()
            ->paginate(24)
            ->appends($request->query());

        return view('index', [
            'items' => $items,
            'activeTab' => $activeTab,
            'keyword' => $request->input('keyword', ''),
        ]);
    }

    public function create()
    {
        $categories = cache()->remember('categories', 3600, function () {
            return Category::orderBy('category_names')->get();
        });
        $conditions = [1 => '良好', 2 => '目立った傷や汚れ無し', 3 => 'やや傷や汚れあり', 4 => '状態が悪い'];
        return view('sell', compact('categories','conditions'));
    }

    public function show(Item $item)
    {
        $item->load(['categories','comments' => function($q){ $q->latest(); }, 'comments.user']);
        $liked = $item->isLikedBy(optional(auth()->user())->id);
        $likeCount = $item->like_counts;
        return view('items.show', compact('item','liked','likeCount'));
    }

    public function comment(Item $item, CommentRequest $request)
    {
        $data = $request->validated();
        $comment = new Comment();
        $comment->item_id = $item->id;
        $comment->user_id = auth()->id();
        $comment->comment_body = $data['comment_body'];
        $comment->save();
        
        // コメント数を更新（明示的に計算）
        $item->comment_counts = $item->comments()->count();
        $item->save();
        
        return back()->with('success', 'コメントを投稿しました');
    }

    public function purchaseForm(Item $item)
    {
        $user = Auth::user();
        
        // 商品が既に売却済みでないかチェック
        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は既に売却済みです。');
        }
        
        // 自分が出品した商品は購入不可
        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $item)->with('error', '自分が出品した商品は購入できません。');
        }
        
        $profile = $user->profile;
        
        // 初期住所を設定（プロフィールから取得）
        $defaultAddress = '';
        if ($profile) {
            $defaultAddress = "〒{$profile->postal_codes}\n{$profile->addresses}";
            if ($profile->building_names) {
                $defaultAddress .= "\n{$profile->building_names}";
            }
        }
        
        // セッションから変更された住所を取得（あれば）
        $currentAddress = Session::get("shipping_address_{$item->id}", $defaultAddress);
        
        return view('purchase.purchase', compact('item', 'defaultAddress', 'currentAddress'));
    }

    public function purchase(Item $item, PurchaseRequest $request)
    {
        $user = Auth::user();
        
        // 商品が既に売却済みでないかチェック
        if ($item->is_sold) {
            return redirect()->route('items.show', $item)->with('error', 'この商品は既に売却済みです。');
        }
        
        // 自分が出品した商品は購入不可
        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $item)->with('error', '自分が出品した商品は購入できません。');
        }
        
        DB::transaction(function() use ($item, $user, $request) {
            // 注文を作成
            Order::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'total_amount' => $item->item_prices,
                'payment_method' => $request->payment_method,
                'shipping_address' => $request->shipping_address,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'trade_status' => Order::TRADE_STATUS_TRADING,
            ]);
            
            // 商品を売却済みにマーク
            $item->update(['is_sold' => true]);
        });
        
        return redirect()->route('items.index')->with('success', '購入が完了しました。');
    }

    public function like(Item $item)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $userId = auth()->id();
        DB::transaction(function() use ($item, $userId) {
            $like = ItemLike::where('item_id', $item->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();
            if ($like) {
                $like->delete();
                if ((int)$item->like_counts > 0) {
                    $item->decrement('like_counts');
                }
            } else {
                ItemLike::create(['item_id' => $item->id, 'user_id' => $userId]);
                $item->increment('like_counts');
            }
        });
        return back();
    }
    public function store(ExhibitionRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // itemsディレクトリが存在しない場合は作成し、777権限を付与
        $itemsPath = storage_path('app/public/items');
        if (!file_exists($itemsPath)) {
            mkdir($itemsPath, 0777, true);
            chmod($itemsPath, 0777);
        }

        // 元のままストレージに保存
        $path = $request->file('item_image')->store('items', 'public');

        $item = Item::create([
            'user_id' => $user->id,
            'item_image_paths' => $path,
            'item_names' => $data['item_name'],
            'brand_names' => $data['brand_name'] ?? '',
            'item_prices' => $data['item_price'],
            'item_descriptions' => $data['item_description'],
            'conditions' => (int)$data['condition'],
        ]);

        // 多対多でカテゴリを紐付け
        if (method_exists($item, 'categories')) {
            $item->categories()->sync($data['category_ids']);
        }

        return redirect('/')->with('success', '出品が完了しました。');
    }
}
