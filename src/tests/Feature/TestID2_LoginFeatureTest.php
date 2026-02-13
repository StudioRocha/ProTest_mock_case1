<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestValidationRules;

/**
 * ログイン機能のFeatureテスト
 * 
 * テストID: 2
 */
class LoginFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレス未入力時にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) ログインページを開く
     *  2) メールアドレスを入力せずに他の必要項目を入力する
     *  3) ログインボタンを押す
     *
     * 期待挙動: 「メールアドレスを入力してください」というバリデーションメッセージが表示される
     */
    public function test_login_without_email_shows_validation_message()
    {
        // 1. ログインページを開く
        $getResponse = $this->get('/login');
        $getResponse->assertStatus(200);

        // 2. メールアドレスを入力せずに他の必要項目を入力する
        $postData = [
            'email' => '', // メールアドレスを空にする
            'password' => TestValidationRules::getLoginTestData()['valid_password'],
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $postData);

        // 期待挙動: バリデーションエラーが発生し、ログインページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 期待挙動: セッションにエラーメッセージが保存される
        $response->assertSessionHasErrors('email');

        // 期待挙動: 正しいエラーメッセージが表示される
        $this->assertContains(
            TestValidationRules::getLoginMessages()['email.required'],
            session('errors')->get('email')
        );
    }

    /**
     * パスワード未入力時にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) ログインページを開く
     *  2) パスワードを入力せずに他の必要項目を入力する
     *  3) ログインボタンを押す
     *
     * 期待挙動: 「パスワードを入力してください」というバリデーションメッセージが表示される
     */
    public function test_login_without_password_shows_validation_message()
    {
        // 1. ログインページを開く
        $getResponse = $this->get('/login');
        $getResponse->assertStatus(200);

        // 2. パスワードを入力せずに他の必要項目を入力する
        $postData = [
            'email' => TestValidationRules::getLoginTestData()['valid_email'],
            'password' => '', // パスワードを空にする
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $postData);

        // 期待挙動: バリデーションエラーが発生し、ログインページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 期待挙動: セッションにエラーメッセージが保存される
        $response->assertSessionHasErrors('password');

        // 期待挙動: 正しいエラーメッセージが表示される
        $this->assertContains(
            TestValidationRules::getLoginMessages()['password.required'],
            session('errors')->get('password')
        );
    }

    /**
     * 登録されていない情報でログイン試行時にバリデーションメッセージが表示されることを検証する
     * 手順:
     *  1) ログインページを開く
     *  2) 必要項目を登録されていない情報を入力する
     *  3) ログインボタンを押す
     *
     * 期待挙動: 「ログイン情報が登録されていません」というバリデーションメッセージが表示される
     */
    public function test_login_with_unregistered_info_shows_validation_message()
    {
        // 1. ログインページを開く
        $getResponse = $this->get('/login');
        $getResponse->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        $postData = [
            'email' => TestValidationRules::getLoginTestData()['invalid_email'], // 登録されていないメールアドレス
            'password' => TestValidationRules::getLoginTestData()['invalid_password'], // 登録されていないパスワード
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $postData);

        // 期待挙動: バリデーションエラーが発生し、ログインページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 期待挙動: セッションにエラーメッセージが保存される
        $response->assertSessionHasErrors('email');

        // 期待挙動: 正しいエラーメッセージが表示される
        $this->assertContains(
            'ログイン情報が登録されていません',
            session('errors')->get('email')
        );
    }

    /**
     * 正しい情報でログイン処理が実行されることを検証する
     * 手順:
     *  1) ログインページを開く
     *  2) 全ての必要項目を入力する
     *  3) ログインボタンを押す
     *
     * 期待挙動: ログイン処理が実行される
     */
    public function test_login_with_valid_data_executes_login_process()
    {
        // テスト用ユーザーを作成（メール認証済み）
        $user = \App\Models\User::factory()->create([
            'email' => TestValidationRules::getLoginTestData()['valid_email'],
            'password' => \Illuminate\Support\Facades\Hash::make(TestValidationRules::getLoginTestData()['valid_password']),
            'email_verified_at' => now(), // メール認証済み
        ]);

        // 1. ログインページを開く
        $getResponse = $this->get('/login');
        $getResponse->assertStatus(200);

        // 2. 全ての必要項目を入力する
        $postData = [
            'email' => TestValidationRules::getLoginTestData()['valid_email'],
            'password' => TestValidationRules::getLoginTestData()['valid_password'],
        ];

        // 3. ログインボタンを押す
        $response = $this->post('/login', $postData);

        // 期待挙動: ログイン処理が実行され、ホームページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/');

        // 期待挙動: ユーザーがログイン状態になっている
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($user);

        // 期待挙動: セッションにエラーがない
        $response->assertSessionHasNoErrors();
    }
}
