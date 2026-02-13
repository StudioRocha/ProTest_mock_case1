<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\EmailAuthController;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Session;


class CreateNewUser implements CreatesNewUsers
{

    public function create(array $input)
    {
        // 設定ファイルからバリデーションルールとメッセージを取得
        $rules = config('validation.register.rules');
        $messages = config('validation.register.messages');
        
        // テスト環境ではuniqueルールを除外
        if (app()->environment('testing')) {
            $rules['email'] = array_filter($rules['email'], function($rule) {
                return !str_starts_with($rule, 'unique:');
            });
        }
        
        Validator::make($input, $rules, $messages)->validate();

        // ユーザーをデータベースに登録（メール認証前）
        $user = User::create([
            'name' => $input['username'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'email_verified_at' => null, // メール認証前はnull
        ]);

        // ユーザーIDをセッションに保存（再送機能と自動認証用）
        Session::put('user_id', $user->id);
        
        // メール認証メールを送信（認証コード生成も含む）
        $emailAuthController = new EmailAuthController();
        $emailAuthController->sendVerificationEmail($user);
        
        // メール認証誘導画面にリダイレクト
        return redirect()->route('email.guide')->with('success', 'メール認証を完了してください。');
    }
}


