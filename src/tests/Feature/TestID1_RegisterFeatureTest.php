<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Support\TestValidationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;

//  * 会員登録機能のFeatureテスト
//  * 
//  * テストID: 1

class RegisterFeatureTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 名前未入力時にバリデーションメッセージが表示されることを検証する。
     * 手順:
     *  1) /register を開く
     *  2) 名前を空にして他の項目を送信
     *  3) 登録ボタン押下時にエラーメッセージが表示される
     */
    public function test_register_requires_username_shows_message()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. 名前を空にして他の必要項目を送信
        // email を空にすることで unique ルール評価を回避し、DB 依存をなくす
        $postData = [
            'username' => '',
            'email' => '',
            'password' => TestValidationRules::getTestData()['valid_password'],
            'password_confirmation' => TestValidationRules::getTestData()['valid_password'],
        ];

        // 3. 登録ボタンを押す → バリデーションエラーで /register に戻る想定
        $response = $this->from('/register')->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 期待挙動: 「お名前を入力してください」というメッセージ
        $response->assertSessionHasErrors([
            'username' => TestValidationRules::getRegisterMessages()['username.required'],
        ]);
    }
    /**
     * メールアドレス未入力時にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) 会員登録ページを開く
     *  2) メールアドレスを入力せずに他の必要項目を入力する
     *  3) 登録ボタンを押す
     * 
     * 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_requires_email_shows_message()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. メールアドレスを空にして他の必要項目を送信
        $postData = [
            'username' => TestValidationRules::getTestData()['valid_username'],
            'email' => '', // メールアドレスを空にする
            'password' => TestValidationRules::getTestData()['valid_password'],
            'password_confirmation' => TestValidationRules::getTestData()['valid_password'],
        ];

        // 3. 登録ボタンを押す → バリデーションエラーで /register に戻る想定
        $response = $this->from('/register')->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 期待挙動: 「メールアドレスを入力してください」というメッセージ
        $response->assertSessionHasErrors([
            'email' => TestValidationRules::getRegisterMessages()['email.required'],
        ]);
    }

    /**
     * パスワード未入力時にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) 会員登録ページを開く
     *  2) パスワードを入力せずに他の必要項目を入力する
     *  3) 登録ボタンを押す
     * 
     * 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_requires_password_shows_message()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. パスワードを空にして他の必要項目を送信
        $postData = [
            'username' => TestValidationRules::getTestData()['valid_username'],
            'email' => TestValidationRules::getTestData()['valid_email'],
            'password' => '', // パスワードを空にする
            'password_confirmation' => TestValidationRules::getTestData()['valid_password'],
        ];

        // 3. 登録ボタンを押す → バリデーションエラーで /register に戻る想定
        $response = $this->from('/register')->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 期待挙動: 「パスワードを入力してください」というメッセージ
        $response->assertSessionHasErrors([
            'password' => TestValidationRules::getRegisterMessages()['password.required'],
        ]);
    }

    /**
     * パスワードが7文字以下の場合にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) 会員登録ページを開く
     *  2) 7文字以下のパスワードと他の必要項目を入力する
     *  3) 登録ボタンを押す
     * 
     * 期待挙動: 「パスワードは8文字以上で入力してください」というバリデーションメッセージが表示される
     */
    public function test_register_password_min_length_shows_message()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. 7文字以下のパスワードと他の必要項目を送信
        $postData = [
            'username' => TestValidationRules::getTestData()['valid_username'],
            'email' => TestValidationRules::getTestData()['valid_email'],
            'password' => TestValidationRules::getTestData()['short_password'], // 7文字（8文字未満）
            'password_confirmation' => TestValidationRules::getTestData()['short_password'],
        ];

        // 3. 登録ボタンを押す → バリデーションエラーで /register に戻る想定
        $response = $this->from('/register')->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 期待挙動: 「パスワードは8文字以上で入力してください」というメッセージ
        $response->assertSessionHasErrors([
            'password' => TestValidationRules::getRegisterMessages()['password.min'],
        ]);
    }

    /**
     * 確認用パスワードと異なるパスワードを入力した場合にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) 会員登録ページを開く
     *  2) 確認用パスワードと異なるパスワードを入力し、他の必要項目も入力する
     *  3) 登録ボタンを押す
     * 
     * 期待挙動: 「パスワードと一致しません」というバリデーションメッセージが表示される
     */
    public function test_register_password_confirmation_mismatch_shows_message()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. 確認用パスワードと異なるパスワードと他の必要項目を送信
        $postData = [
            'username' => TestValidationRules::getTestData()['valid_username'],
            'email' => TestValidationRules::getTestData()['valid_email'],
            'password' => TestValidationRules::getTestData()['valid_password'],
            'password_confirmation' => TestValidationRules::getTestData()['different_password'], // 異なるパスワード
        ];

        // 3. 登録ボタンを押す → バリデーションエラーで /register に戻る想定
        $response = $this->from('/register')->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/register');

        // 期待挙動: 「パスワードと一致しません」というメッセージ
        $response->assertSessionHasErrors([
            'password' => TestValidationRules::getRegisterMessages()['password.confirmed'],
        ]);
    }

    /**
     * 全ての必要項目を正しく入力した場合に会員情報が登録され、プロフィール設定画面に遷移することを検証する
     * 手順:
     *  1) 会員登録ページを開く
     *  2) 全ての必要項目を正しく入力する
     *  3) 登録ボタンを押す
     * 
     * 期待挙動: 会員情報が登録され、メール認証誘導画面に遷移する
     */
    public function test_register_with_valid_data_redirects_to_profile()
    {
        // 1. 会員登録ページを開く
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(200);

        // 2. 全ての必要項目を正しく入力
        $postData = [
            'username' => TestValidationRules::getTestData()['valid_username'],
            'email' => TestValidationRules::getTestData()['valid_email'],
            'password' => TestValidationRules::getTestData()['valid_password'],
            'password_confirmation' => TestValidationRules::getTestData()['valid_password'],
        ];

        // 3. 登録ボタンを押す → メール認証誘導画面にリダイレクト
        $response = $this->post('/register', $postData);

        $response->assertStatus(302);
        $response->assertRedirect('/email/guide');

        // 期待挙動: ユーザーがデータベースに登録されている
        $this->assertDatabaseHas('users', [
            'name' => TestValidationRules::getTestData()['valid_username'],
            'email' => TestValidationRules::getTestData()['valid_email'],
            'email_verified_at' => null, // メール認証前はnull
        ]);

        // 期待挙動: セッションにuser_idが保存されている（自動認証用）
        $this->assertNotNull(session('user_id'));
        
        // 期待挙動: メール認証誘導画面にリダイレクトされる
        $response->assertRedirect('/email/guide');
    }

}
