<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\Order;
use App\Models\Message;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('profile'); // プロフィール情報を事前読み込み
        $activeTab = $request->query('page', 'sell');

        $listedItems = Item::where('user_id', $user->id)
            ->with(['user', 'categories'])
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $purchasedItems = Order::where('user_id', $user->id)
            ->with(['item.user', 'item.categories'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function($order){
                return $order->item;
            })
            ->filter();

        // 取引中の商品を取得（出品者または購入者として）
        // 購入者の場合、自分が評価済みの取引は除外（出品者は引き続き表示）
        $tradingOrders = Order::where(function($query) use ($user) {
                // 購入者として（ただし、自分が評価済みのものは除外）
                $query->where('user_id', $user->id)
                      ->whereDoesntHave('ratings', function($q) use ($user) {
                          $q->where('rater_id', $user->id);
                      })
                      // または出品者として
                      ->orWhereHas('item', function($q) use ($user) {
                          $q->where('user_id', $user->id);
                      });
            })
            ->where('trade_status', Order::TRADE_STATUS_TRADING)
            ->with(['item.user', 'item.categories'])
            ->get()
            ->sortByDesc(function($order) {
                // 最新メッセージの作成日時で並び替え
                $latestMessage = Message::where('item_id', $order->item_id)
                    ->orderByDesc('created_at')
                    ->first();
                return $latestMessage ? $latestMessage->created_at : $order->created_at;
            })
            ->values();

        // 各商品の未読メッセージ数を計算
        $itemUnreadCounts = [];
        $unreadMessageCount = 0;
        
        foreach ($tradingOrders as $order) {
            $itemId = $order->item_id;
            
            // 購入者か出品者かを判定
            $isBuyer = $order->user_id === $user->id;
            
            // 最後に閲覧した時刻を取得
            $lastViewedAt = $isBuyer ? $order->buyer_last_viewed_at : $order->seller_last_viewed_at;
            
            $itemUnreadCount = 0;
            
            if ($lastViewedAt) {
                // 最後に閲覧した時刻以降の、自分以外のユーザーからのメッセージ数をカウント
                $itemUnreadCount = Message::where('item_id', $itemId)
                    ->where('created_at', '>', $lastViewedAt)
                    ->where('user_id', '!=', $user->id)
                    ->count();
            } else {
                // まだ一度も閲覧していない場合、自分以外のユーザーからのすべてのメッセージが未読
                $itemUnreadCount = Message::where('item_id', $itemId)
                    ->where('user_id', '!=', $user->id)
                    ->count();
            }
            
            $itemUnreadCounts[$itemId] = $itemUnreadCount;
            $unreadMessageCount += $itemUnreadCount;
        }

        // 評価平均を計算
        $averageRating = $user->receivedRatings()->avg('rating');
        $roundedRating = $averageRating ? round($averageRating) : null;

        return view('mypage.index', [
            'user' => $user,
            'activeTab' => $activeTab,
            'listedItems' => $listedItems,
            'purchasedItems' => $purchasedItems,
            'tradingOrders' => $tradingOrders,
            'itemUnreadCounts' => $itemUnreadCounts,
            'unreadMessageCount' => $unreadMessageCount,
            'averageRating' => $roundedRating,
        ]);
    }
}


