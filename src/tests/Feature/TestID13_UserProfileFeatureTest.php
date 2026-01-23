<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Profile;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * ユーザー情報取得機能のFeatureテスト
 *
 * テストID: 13
 */
class UserProfileFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィールページでユーザー情報が正しく表示されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) プロフィールページを開く
     *
     * 期待挙動: プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧が正しく表示される
     */
    public function test_user_profile_page_displays_all_user_information()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // プロフィールを作成
        $profile = Profile::create([
            'user_id' => $user->id,
            'usernames' => 'テストユーザー',
            'postal_codes' => '100-0001',
            'addresses' => '東京都千代田区千代田1-1-1',
            'building_names' => 'テストマンション101号',
            'avatar_paths' => 'avatars/test-avatar.jpg',
        ]);

        // カテゴリーを作成
        $category = Category::create([
            'category_names' => 'ファッション',
        ]);

        // 出品した商品を作成
        $listedItemData = TestItemData::getRandomItems(1)[0];
        $listedItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => $listedItemData['item_names'],
            'brand_names' => $listedItemData['brand_names'],
            'item_prices' => $listedItemData['item_prices'],
            'item_descriptions' => $listedItemData['item_descriptions'],
            'conditions' => $listedItemData['conditions'],
        ]);
        $listedItem->categories()->attach($category->id);

        // 購入した商品を作成（別のユーザーが出品）
        /** @var \App\Models\User $seller */
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
        ]);

        $purchasedItemData = TestItemData::getRandomItems(1)[0];
        $purchasedItem = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => $purchasedItemData['item_names'],
            'brand_names' => $purchasedItemData['brand_names'],
            'item_prices' => $purchasedItemData['item_prices'],
            'item_descriptions' => $purchasedItemData['item_descriptions'],
            'conditions' => $purchasedItemData['conditions'],
        ]);
        $purchasedItem->categories()->attach($category->id);

        // 購入履歴を作成
        Order::create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
            'total_amount' => $purchasedItem->item_prices,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'payment_method' => 'credit_card',
            'shipping_address' => '東京都渋谷区恵比寿2-2-2',
        ]);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. プロフィールページを開く
        $response = $this->get('/mypage');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: プロフィール画像が表示される
        $response->assertSee('storage/avatars/test-avatar.jpg');

        // 期待挙動: ユーザー名が表示される
        $response->assertSee('テストユーザー');

        // 期待挙動: 出品した商品タブが表示される
        $response->assertSee('出品した商品');

        // 期待挙動: 購入した商品タブが表示される
        $response->assertSee('購入した商品');

        // 期待挙動: 購入した商品タブをクリックして商品を確認
        $buyTabResponse = $this->get('/mypage?page=buy');
        $buyTabResponse->assertSee($purchasedItem->item_names);
        $buyTabResponse->assertSee('Sold');
    }
}
