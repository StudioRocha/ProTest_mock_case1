<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Category;

/**
 * ユーザー情報変更機能のFeatureテスト
 *
 * テストID: 14
 */
class UserProfileUpdateFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィール編集ページで変更項目の初期値が正しく表示されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) プロフィールページを開く
     *
     * 期待挙動: 各項目の初期値が正しく表示されている
     */
    public function test_profile_edit_page_displays_correct_initial_values()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // プロフィールを作成（過去設定された値）
        $profile = Profile::create([
            'user_id' => $user->id,
            'usernames' => '山田太郎',
            'postal_codes' => '150-0002',
            'addresses' => '東京都渋谷区恵比寿2-2-2',
            'building_names' => '恵比寿タワー202号',
            'avatar_paths' => 'avatars/yamada-avatar.jpg',
        ]);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. プロフィールページを開く
        $response = $this->get('/mypage/profile');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: プロフィール画像の初期値が正しく表示される
        $response->assertSee('storage/avatars/yamada-avatar.jpg');

        // 期待挙動: ユーザー名の初期値が正しく表示される
        $response->assertSee('value="山田太郎"', false);

        // 期待挙動: 郵便番号の初期値が正しく表示される
        $response->assertSee('value="150-0002"', false);

        // 期待挙動: 住所の初期値が正しく表示される
        $response->assertSee('value="東京都渋谷区恵比寿2-2-2"', false);

        // 期待挙動: 建物名の初期値が正しく表示される
        $response->assertSee('value="恵比寿タワー202号"', false);

        // 期待挙動: フォームの各フィールドが正しく設定されている
        $response->assertSee('name="username"', false);
        $response->assertSee('name="postal_code"', false);
        $response->assertSee('name="address"', false);
        $response->assertSee('name="building_name"', false);
        $response->assertSee('name="avatar"', false);

        // 期待挙動: 更新ボタンが表示される
        $response->assertSee('更新する');
    }
}
