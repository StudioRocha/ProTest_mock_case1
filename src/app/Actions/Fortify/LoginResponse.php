<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\Item;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();
        
        // チャットアクセストークンがセッションにある場合（メール内リンクからログインした場合）
        if (Session::has('chat_access_token') && Session::has('chat_access_item_id')) {
            $token = Session::get('chat_access_token');
            $itemId = Session::get('chat_access_item_id');
            
            // トークンからOrderを取得
            $order = Order::where('item_id', $itemId)
                ->where('chat_access_token', $token)
                ->where('chat_access_token_expires_at', '>', now())
                ->first();
            
            if ($order) {
                // 購入者と出品者を取得
                $buyer = $order->user;
                $seller = $order->item->user;
                
                // ログインしたユーザーが取引に関与しているか確認
                if ($user->id === $buyer->id || $user->id === $seller->id) {
                    // トークンを無効化
                    $order->update([
                        'chat_access_token' => null,
                        'chat_access_token_expires_at' => null,
                    ]);
                    
                    // セッションからトークン情報を削除
                    Session::forget('chat_access_token');
                    Session::forget('chat_access_item_id');
                    
                    $item = Item::find($itemId);
                    if ($item) {
                        return redirect()->route('chat.index', $item)
                            ->with('success', 'ログインしました。取引チャット画面を開きました。');
                    }
                } else {
                    // 取引に関与していないユーザーでログインした場合
                    Session::forget('chat_access_token');
                    Session::forget('chat_access_item_id');
                    return redirect()->route('items.index')
                        ->with('error', 'この取引チャットにアクセスする権限がありません。');
                }
            } else {
                // トークンが無効または期限切れ
                Session::forget('chat_access_token');
                Session::forget('chat_access_item_id');
                return redirect()->route('items.index')
                    ->with('error', '無効なリンク、またはリンクの有効期限が切れています。');
            }
        }
        
        // 通常のログイン処理
        if ($user && !$user->profile) {
            return redirect()->to('/mypage/profile');
        }
        return redirect()->intended('/');
    }
}


