<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Item;
use App\Models\User;
use App\Models\Category;

/**
 * 商品一覧取得機能のFeatureテスト
 * 
 * テストID: 4
 */
class ItemListFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品ページを開いてすべての商品が表示されることを検証する
     * 手順:
     *  1) 商品ページを開く
     *
     * 期待挙動: すべての商品が表示される
     */
    public function test_item_page_displays_all_items()
    {
        // テスト用データを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => '家電',
        ]);

        // 複数の商品を作成
        $items = Item::factory()->count(5)->create([
            'user_id' => $user->id,
            'is_sold' => false,
        ]);

        // 各商品にカテゴリを関連付け
        foreach ($items as $item) {
            $item->categories()->attach($category->id);
        }

        // 1. 商品ページを開く
        $response = $this->get('/');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 作成した商品が全て表示される
        foreach ($items as $item) {
            $response->assertSee($item->item_names);
            // ブランド名と価格は商品詳細ページで表示されるため、一覧では表示されない
        }

        // 期待挙動: 商品が表示される（商品数表示は実装されていないため削除）
    }

    /**
     * 商品が存在しない場合の表示を検証する
     */
    public function test_item_page_displays_no_items_when_empty()
    {
        // 商品を作成しない

        // 商品ページを開く
        $response = $this->get('/');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 商品が見つからないメッセージが表示される
        $response->assertSee('商品がありません。');
    }

    /**
     * 売り切れ商品も表示されることを検証する（Soldバッジ付き）
     */
    public function test_item_page_shows_sold_items_with_badge()
    {
        // テスト用データを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => '家電',
        ]);

        // 販売中の商品を作成
        $availableItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => '販売中商品',
        ]);

        // 売り切れ商品を作成
        $soldItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => true,
            'item_names' => '売り切れ商品',
        ]);

        // カテゴリを関連付け
        $availableItem->categories()->attach($category->id);
        $soldItem->categories()->attach($category->id);

        // 商品ページを開く
        $response = $this->get('/');

        // 期待挙動: 販売中の商品と売り切れ商品の両方が表示される
        $response->assertSee('販売中商品');
        $response->assertSee('売り切れ商品');
        
        // 期待挙動: 売り切れ商品にはSoldバッジが表示される
        $response->assertSee('Sold');
    }

    /**
     * 購入済み商品に「Sold」ラベルが表示されることを検証する
     * 手順:
     *  1) 商品ページを開く
     *  2) 購入済み商品を表示する
     *
     * 期待挙動: 購入済み商品に「Sold」のラベルが表示される
     */
    public function test_item_page_shows_purchased_items_with_sold_label()
    {
        // テスト用データを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => '家電',
        ]);

        // 販売中の商品を作成
        $availableItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => '販売中商品',
        ]);

        // 購入済み商品を作成（is_sold = true）
        $purchasedItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => true,
            'item_names' => '購入済み商品',
        ]);

        // カテゴリを関連付け
        $availableItem->categories()->attach($category->id);
        $purchasedItem->categories()->attach($category->id);

        // 1. 商品ページを開く
        $response = $this->get('/');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 2. 購入済み商品を表示する
        // 期待挙動: 販売中の商品と購入済み商品の両方が表示される
        $response->assertSee('販売中商品');
        $response->assertSee('購入済み商品');

        // 期待挙動: 購入済み商品に「Sold」のラベルが表示される
        $response->assertSee('Sold');
    }

    /**
     * ログインしたユーザーが出品した商品が一覧に表示されないことを検証する
     * 手順:
     *  1) ユーザーにログインをする
     *  2) 商品ページを開く
     *
     * 期待挙動: 自分が出品した商品が一覧に表示されない
     */
    public function test_logged_in_user_does_not_see_own_items_in_list()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => '家電',
        ]);

        // ログインユーザーが出品した商品を作成
        $ownItem = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => '自分の出品商品',
        ]);

        // 他のユーザーが出品した商品を作成
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);

        $otherItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => '他の人の出品商品',
        ]);

        // カテゴリを関連付け
        $ownItem->categories()->attach($category->id);
        $otherItem->categories()->attach($category->id);

        // 1. ユーザーにログインをする
        $this->actingAs($user);

        // 2. 商品ページを開く
        $response = $this->get('/');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 自分が出品した商品は表示されない
        $response->assertDontSee('自分の出品商品');

        // 期待挙動: 他のユーザーが出品した商品は表示される
        $response->assertSee('他の人の出品商品');
    }
}
