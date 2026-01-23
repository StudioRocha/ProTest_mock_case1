<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class BlockUnverifiedUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // ログインしているかチェック
        if (Auth::check()) {
            $user = Auth::user();
            
            // メール認証が完了していない場合は強制的にログアウトして誘導画面へ
            if (!$user->email_verified_at) {
                Auth::logout();
                return redirect()->route('email.guide')->with('error', 'メール認証を完了してからアクセスしてください。');
            }
        }
        
        // 未ログインでも、登録直後にセッションへ保存されたユーザーIDがあり
        // かつメール未認証の場合はログイン画面等へのアクセスをブロック
        if (!Auth::check()) {
            $pendingUserId = Session::get('user_id');
            if ($pendingUserId) {
                $pendingUser = User::find($pendingUserId);
                if ($pendingUser && !$pendingUser->email_verified_at) {
                    return redirect()->route('email.guide')->with('error', 'まだ登録は完了していません。メール認証を完了してください。');
                }
            }
        }
        
        return $next($request);
    }
}
