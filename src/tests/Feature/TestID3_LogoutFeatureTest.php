<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

/**
 * ログアウト機能のFeatureテスト
 * 
 * テストID: 3
 */
class LogoutFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインしたユーザーがログアウト処理を実行できることを検証する
     * 手順:
     *  1) ユーザーにログインをする
     *  2) ログアウトボタンを押す
     *
     * 期待挙動: ログアウト処理が実行される
     */
    public function test_logged_in_user_can_logout()
    {
        // テスト用ユーザーを作成（メール認証済み）
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // 1. ユーザーにログインをする
        $this->actingAs($user);

        // ログイン状態であることを確認
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($user);

        // 2. ログアウトボタンを押す
        $response = $this->post('/logout');

        // 期待挙動: ログアウト処理が実行され、ログインページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 期待挙動: ユーザーがログアウト状態になっている
        $this->assertGuest();
        $this->assertNull(auth()->user());

        // 期待挙動: セッションにエラーがない
        $response->assertSessionHasNoErrors();
    }

    /**
     * 未ログイン状態でログアウトを試行してもエラーが発生しないことを検証する
     */
    public function test_logout_without_login_does_not_cause_error()
    {
        // 未ログイン状態であることを確認
        $this->assertGuest();

        // ログアウトボタンを押す
        $response = $this->post('/logout');

        // 期待挙動: エラーが発生せず、ログインページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/login');

        // 期待挙動: 依然として未ログイン状態である
        $this->assertGuest();
        $this->assertNull(auth()->user());

        // 期待挙動: セッションにエラーがない
        $response->assertSessionHasNoErrors();
    }

    /**
     * ログアウト後に認証が必要なページにアクセスできないことを検証する
     */
    public function test_logout_prevents_access_to_protected_pages()
    {
        // テスト用ユーザーを作成（メール認証済み）
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // ユーザーにログインをする
        $this->actingAs($user);

        // ログイン状態であることを確認
        $this->assertAuthenticated();

        // ログアウトを実行
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertStatus(302);

        // 期待挙動: ログアウト後に認証が必要なページにアクセスできない
        $protectedPageResponse = $this->get('/mypage');
        $protectedPageResponse->assertStatus(302);
        $protectedPageResponse->assertRedirect('/login');

        // 期待挙動: 依然として未ログイン状態である
        $this->assertGuest();
    }
}
