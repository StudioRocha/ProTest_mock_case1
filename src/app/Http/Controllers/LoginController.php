<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use App\Models\Order;
use App\Models\Item;

class LoginController extends Controller
{
    public function show()
    {
        // 既にログインしている場合はホームにリダイレクト
        if (Auth::check()) {
            $user = Auth::user();
            
            // メール認証が完了していない場合は誘導画面へ
            if (!$user->email_verified_at) {
                return redirect()->route('email.guide');
            }
            
            return redirect()->intended('/');
        }
        
        return View::make('auth.login');
    }

    public function login(Request $request)
    {
        $rules = config('validation.login.rules');
        $messages = config('validation.login.messages');
        
        $credentials = $request->validate($rules, $messages);
        
            if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // メール認証が完了していない場合は必ず誘導画面へ
            if (!$user->email_verified_at) {
                Auth::logout(); // ログイン状態を解除
                return redirect()->route('email.guide')->with('error', 'メール認証を完了してからログインしてください。');
            }
            
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
            
            return redirect()->intended('/');
        }
        
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}


