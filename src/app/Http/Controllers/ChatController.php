<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Models\Item;
use App\Models\Order;
use App\Models\Message;
use App\Models\Rating;
use App\Http\Requests\ChatMessageRequest;
use App\Mail\TradeCompletedMail;

class ChatController extends Controller
{
    /**
     * チャット画面を表示
     */
    public function index(Item $item)
    {
        $user = Auth::user();

        // 取引中のOrderを取得（購入者または出品者として）
        // 購入者が評価を送信した後でも出品者が評価できるように、TRADING状態のものを取得
        $order = Order::where('item_id', $item->id)
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->where(function($query) use ($user) {
                // 購入者として
                $query->where('user_id', $user->id)
                      // または出品者として
                      ->orWhereHas('item', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->with(['item.user', 'user', 'ratings'])
            ->first();

        if (!$order) {
            return redirect()->route('mypage.index', ['page' => 'trading'])
                ->with('error', '取引中の商品が見つかりません。');
        }

        // 取引相手を取得
        $otherUser = $order->user_id === $user->id 
            ? $order->item->user  // 購入者の場合、出品者
            : $order->user;        // 出品者の場合、購入者

        // メッセージを取得
        $messages = Message::where('item_id', $item->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // その他の取引中の商品を取得（サイドバー用）
        $otherTradingOrders = Order::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('item', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->where('item_id', '!=', $item->id)
            ->with(['item'])
            ->orderByDesc('updated_at')
            ->get();

        // 購入者かどうかを判定
        $isBuyer = $order->user_id === $user->id;

        // 既読情報を更新する前に、前回の既読時刻を取得
        $lastViewedAt = $isBuyer ? $order->buyer_last_viewed_at : $order->seller_last_viewed_at;

        // 既に取得済みの$messagesコレクションから既読位置を特定（追加クエリなし）
        $lastReadMessageId = null;
        if ($lastViewedAt && $messages->isNotEmpty()) {
            foreach ($messages as $message) {
                if ($message->created_at <= $lastViewedAt) {
                    $lastReadMessageId = $message->id;
                } else {
                    break; // 時系列順なので、超えたら終了
                }
            }
        }

        // 既読情報を更新（チャット画面を開いた時点で既読とする）
        if ($isBuyer) {
            $order->update(['buyer_last_viewed_at' => now()]);
        } else {
            $order->update(['seller_last_viewed_at' => now()]);
        }

        // セッションから入力情報を取得（入力情報保持機能）
        $savedMessage = session("chat_message_{$item->id}", '');

        // 購入者が評価済みかチェック
        $buyerHasRated = Rating::where('order_id', $order->id)
            ->where('rater_id', $order->user_id)
            ->exists();
        
        // 出品者が評価済みかチェック
        $sellerHasRated = Rating::where('order_id', $order->id)
            ->where('rater_id', $order->item->user_id)
            ->exists();
        
        // 出品者が評価できるか（購入者が評価済みで、出品者がまだ評価していない場合）
        $canSellerRate = !$isBuyer && $buyerHasRated && !$sellerHasRated;

        return view('chat.index', [
            'item' => $item,
            'order' => $order,
            'otherUser' => $otherUser,
            'messages' => $messages,
            'otherTradingOrders' => $otherTradingOrders,
            'isBuyer' => $isBuyer,
            'savedMessage' => $savedMessage,
            'lastReadMessageId' => $lastReadMessageId,
            'canSellerRate' => $canSellerRate,
            'buyerHasRated' => $buyerHasRated,
        ]);
    }

    /**
     * メッセージを投稿
     */
    public function store(ChatMessageRequest $request, Item $item)
    {
        $user = Auth::user();

        // 取引中のOrderを確認
        $order = Order::where('item_id', $item->id)
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('item', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->first();

        if (!$order) {
            return redirect()->route('mypage.index', ['page' => 'trading'])
                ->with('error', '取引中の商品が見つかりません。');
        }

        // 画像アップロード処理
        $imagePath = null;
        if ($request->hasFile('image')) {
            // messagesディレクトリが存在しない場合は作成
            $messagesPath = storage_path('app/public/messages');
            if (!file_exists($messagesPath)) {
                mkdir($messagesPath, 0777, true);
                chmod($messagesPath, 0777);
            }
            
            $imagePath = $request->file('image')->store('messages', 'public');
        }

        // メッセージを作成
        Message::create([
            'item_id' => $item->id,
            'user_id' => $user->id,
            'message' => $request->message,
            'image_path' => $imagePath,
        ]);

        // Orderのupdated_atを更新（並び替え用）
        $order->touch();

        // セッションから入力情報を削除
        session()->forget("chat_message_{$item->id}");

        return redirect()->route('chat.index', $item);
    }

    /**
     * メッセージを編集
     */
    public function update(ChatMessageRequest $request, Item $item, Message $message)
    {
        $user = Auth::user();

        // 自分のメッセージのみ編集可能
        if ($message->user_id !== $user->id) {
            return redirect()->route('chat.index', $item);
        }

        // メッセージを更新
        $message->update([
            'message' => $request->message,
        ]);

        return redirect()->route('chat.index', $item);
    }

    /**
     * メッセージを削除
     */
    public function destroy(Item $item, Message $message)
    {
        $user = Auth::user();

        // 自分のメッセージのみ削除可能
        if ($message->user_id !== $user->id) {
            return redirect()->route('chat.index', $item);
        }

        // メッセージを削除（ソフトデリート）
        $message->delete();

        return redirect()->route('chat.index', $item);
    }

    /**
     * 取引を完了（購入者のみ）
     */
    public function complete(Request $request, Item $item)
    {
        $user = Auth::user();

        // 取引中のOrderを確認（購入者のみ）
        $order = Order::where('item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->first();

        if (!$order) {
            return redirect()->route('chat.index', $item)
                ->with('error', '取引中の商品が見つかりません。');
        }

        // 既に評価済みかチェック
        $existingRating = Rating::where('order_id', $order->id)
            ->where('rater_id', $user->id)
            ->first();

        if ($existingRating) {
            return redirect()->route('chat.index', $item)
                ->with('error', '既に評価済みです。');
        }

        // 評価のバリデーション
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'rating.required' => '評価を選択してください',
            'rating.integer' => '評価は数値で入力してください',
            'rating.min' => '評価は1以上で入力してください',
            'rating.max' => '評価は5以下で入力してください',
        ]);

        // 評価される人（出品者）を取得
        $ratedUser = $order->item->user;

        // 評価を保存
        Rating::create([
            'order_id' => $order->id,
            'rater_id' => $user->id,
            'rated_id' => $ratedUser->id,
            'rating' => $request->rating,
        ]);

        // 商品購入者が取引を完了したので、出品者宛に通知メールを送信
        $buyer = $order->user;
        $seller = $ratedUser;
        
        Mail::to($seller->email)->send(new TradeCompletedMail($order, $seller, $buyer));

        return redirect()->route('items.index')
            ->with('success', '評価を投稿しました。');
    }

    /**
     * 入力情報をセッションに保存（入力情報保持機能）
     */
    public function saveDraft(Request $request, Item $item)
    {
        $message = $request->input('message', '');
        Session::put("chat_message_{$item->id}", $message);
        return response()->json(['success' => true]);
    }

    /**
     * 既読情報を更新（非同期処理用）
     */
    public function markAsRead(Request $request, Item $item)
    {
        $user = Auth::user();

        // 取引中のOrderを取得
        $order = Order::where('item_id', $item->id)
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('item', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => '取引中の商品が見つかりません。'], 404);
        }

        // 既読情報を更新
        $isBuyer = $order->user_id === $user->id;
        if ($isBuyer) {
            $order->update(['buyer_last_viewed_at' => now()]);
        } else {
            $order->update(['seller_last_viewed_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * 出品者が評価を送信（購入者が評価済みの場合のみ）
     */
    public function sellerRate(Request $request, Item $item)
    {
        $user = Auth::user();

        // 取引中のOrderを確認（出品者のみ）
        $order = Order::where('item_id', $item->id)
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->whereHas('item', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if (!$order) {
            return redirect()->route('chat.index', $item)
                ->with('error', '取引中の商品が見つかりません。');
        }

        // 購入者が評価済みかチェック
        $buyerRating = Rating::where('order_id', $order->id)
            ->where('rater_id', $order->user_id)
            ->first();

        if (!$buyerRating) {
            return redirect()->route('chat.index', $item)
                ->with('error', '購入者がまだ評価を送信していません。');
        }

        // 出品者が既に評価済みかチェック
        $existingSellerRating = Rating::where('order_id', $order->id)
            ->where('rater_id', $user->id)
            ->first();

        if ($existingSellerRating) {
            return redirect()->route('chat.index', $item)
                ->with('error', '既に評価済みです。');
        }

        // 評価のバリデーション
        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'rating.required' => '評価を選択してください',
            'rating.integer' => '評価は数値で入力してください',
            'rating.min' => '評価は1以上で入力してください',
            'rating.max' => '評価は5以下で入力してください',
        ]);

        // 評価される人（購入者）を取得
        $ratedUser = $order->user;

        // 評価を保存
        Rating::create([
            'order_id' => $order->id,
            'rater_id' => $user->id,
            'rated_id' => $ratedUser->id,
            'rating' => $request->rating,
        ]);

        // 両方の評価が揃ったので取引を完了にする
        $order->update([
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
        ]);

        return redirect()->route('items.index')
            ->with('success', '取引を完了し、評価を投稿しました。');
    }

    /**
     * メール内のトークンからチャット画面にアクセス（確認画面を表示）
     */
    public function access(Request $request, Item $item, $token)
    {
        // トークンからOrderを取得
        $order = Order::where('item_id', $item->id)
            ->where('chat_access_token', $token)
            ->where('chat_access_token_expires_at', '>', now())
            ->with(['item.user', 'user'])
            ->first();

        if (!$order) {
            return redirect()->route('items.index')
                ->with('error', '無効なリンク、またはリンクの有効期限が切れています。');
        }

        // 購入者と出品者を取得
        $buyer = $order->user;
        $seller = $order->item->user;
        
        // メールは出品者に送られるので、targetUserは出品者
        $targetUser = $seller;

        // 現在ログインしているユーザーを取得
        $currentUser = Auth::user();

        // 既に正しいユーザー（出品者）でログインしている場合
        if ($currentUser && $currentUser->id === $targetUser->id) {
            // トークンを無効化
            $order->update([
                'chat_access_token' => null,
                'chat_access_token_expires_at' => null,
            ]);
            
            return redirect()->route('chat.index', $item);
        }

        // 別アカウントでログインしている、または未ログインの場合
        // 確認画面を表示
        return view('chat.confirm-switch', [
            'item' => $item,
            'order' => $order,
            'targetUser' => $targetUser,
            'currentUser' => $currentUser,
            'token' => $token,
        ]);
    }

    /**
     * 確認後にログアウトしてログイン画面に遷移
     */
    public function switchAccount(Request $request, Item $item, $token)
    {
        // トークンからOrderを取得
        $order = Order::where('item_id', $item->id)
            ->where('chat_access_token', $token)
            ->where('chat_access_token_expires_at', '>', now())
            ->with(['item.user', 'user'])
            ->first();

        if (!$order) {
            return redirect()->route('items.index')
                ->with('error', '無効なリンク、またはリンクの有効期限が切れています。');
        }

        // 現在ログインしている場合はログアウト
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // ログイン後にチャット画面に遷移するための情報をセッションに保存
        Session::put('chat_access_token', $token);
        Session::put('chat_access_item_id', $item->id);

        return redirect()->route('login')
            ->with('info', 'この取引チャットにアクセスするにはログインが必要です。');
    }
}
