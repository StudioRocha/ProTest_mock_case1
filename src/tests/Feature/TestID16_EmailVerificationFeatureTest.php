<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

/**
 * メール認証機能のFeatureテスト
 *
 * テストID: 16
 */
class EmailVerificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録後に認証メールが送信されることを検証する
     * 手順:
     *  1) 会員登録をする
     *  2) 認証メールを送信する
     *
     * 期待挙動: 登録したメールアドレス宛に認証メールが送信されている
     */
    public function test_verification_email_is_sent_after_user_registration()
    {
        // メール送信をスパイして、実際の send 呼び出しと引数を検証する
        Mail::spy();

        // 1. 会員登録をする
        $userData = [
            'username' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        // 期待挙動: 登録が成功し、メール認証ガイドページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/email/guide');

        // 期待挙動: ユーザーがデータベースに作成されている
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null, // まだ認証されていない
        ]);

        // 期待挙動: ユーザーが作成されている
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 期待挙動: 認証トークンが生成されている
        $this->assertNotNull($user->verification_token);
        $this->assertNotNull($user->verification_token_expires_at);

        // 期待挙動: Mail::send がテンプレートと宛先を伴って呼ばれている
        Mail::shouldHaveReceived('send')
            ->atLeast()->once()
            ->with(
                'emails.verification',
                \Mockery::on(function ($data) use ($user) {
                    return isset($data['user']) && $data['user']->email === $user->email
                        && isset($data['verificationUrl']) && is_string($data['verificationUrl'])
                        && strpos($data['verificationUrl'], '/email/verify/') !== false;
                }),
                \Mockery::on(function ($callback) use ($user) {
                    // クロージャに渡される $message に対して to()/subject() が設定されることを検証
                    $message = new class {
                        public array $to = [];
                        public ?string $subject = null;
                        public function to($address) { $this->to[] = $address; return $this; }
                        public function subject($text) { $this->subject = $text; return $this; }
                    };
                    $callback($message);
                    return in_array($user->email, $message->to, true)
                        && $message->subject === 'メール認証を完了してください';
                })
            );
    }



    /**
     * メール認証導線画面が正しく表示されることを検証する
     * 手順:
     *  1) メール認証導線画面を表示する
     *
     * 期待挙動: メール認証導線画面が表示され、「認証はこちらから」ボタンと再送リンクが表示される
     */
    public function test_email_verification_guide_page_displays_correctly()
    {
        // 1. メール認証導線画面を表示する
        $response = $this->get('/email/guide');
        
        // 期待挙動: 導線画面が表示される
        $response->assertStatus(200);
        $response->assertSee('メール認証を完了してください');
        $response->assertSee('認証はこちらから');
        $response->assertSee('認証メールを再送する');
        
        // 期待挙動: 「認証はこちらから」ボタンがMailHogへのリンクになっている
        $response->assertSee('http://localhost:8025');
    }

    /**
     * メール認証完了後にプロフィール設定画面に遷移することを検証する
     * 手順:
     *  1) メール認証URLにアクセスして認証を完了する
     *  2) プロフィール設定画面を表示する
     *
     * 期待挙動: プロフィール設定画面に遷移する
     */
    public function test_email_verification_completion_navigates_to_profile_settings()
    {
        // テスト用ユーザーを作成
        $verificationToken = 'test_verification_token_123456789012345678901234567890123456789012345678901234567890';
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
            'verification_token' => $verificationToken,
            'verification_token_expires_at' => now()->addHours(24),
        ]);

        // セッションにユーザーIDを設定
        session(['user_id' => $user->id]);

        // 1. メール認証URLにアクセスして認証を完了する
        $response = $this->get('/email/verify/' . $verificationToken);

        // 期待挙動: 認証が完了し、プロフィール設定画面にリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect('/mypage/profile');

        // 期待挙動: ユーザーが認証済みになっている
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_token);
        $this->assertNull($user->verification_token_expires_at);

        // 期待挙動: ユーザーがログインしている
        $this->assertAuthenticated();

        // 2. プロフィール設定画面を表示する
        $response = $this->get('/mypage/profile');

        // 期待挙動: プロフィール設定画面が表示される
        $response->assertStatus(200);
        $response->assertSee('プロフィール設定');
        $response->assertSee('ユーザー名');
        $response->assertSee('郵便番号');
        $response->assertSee('住所');
        $response->assertSee('建物名');
    }
}