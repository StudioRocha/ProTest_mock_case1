<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\ItemLike;

/**
 * マイリスト一覧取得機能のFeatureテスト
 * 
 * テストID: 5
 */
class MyListFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインしたユーザーがいいねをした商品がマイリストに表示されることを検証する
     * 手順:
     *  1) ユーザーにログインをする
     *  2) マイリストページを開く
     *
     * 期待挙動: いいねをした商品が表示される
     */
    public function test_logged_in_user_can_see_liked_items_in_mylist()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 他のユーザーを作成（出品者用）
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => 'ファッション',
        ]);

        // いいねする商品を作成（他のユーザーが出品）
        $likedItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => 'いいねした商品',
        ]);

        // いいねしない商品を作成（他のユーザーが出品）
        $notLikedItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => 'いいねしていない商品',
        ]);

        // カテゴリを関連付け
        $likedItem->categories()->attach($category->id);
        $notLikedItem->categories()->attach($category->id);

        // いいねを作成
        ItemLike::create([
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // 1. ユーザーにログインをする
        $this->actingAs($user);

        // 2. マイリストページを開く
        $response = $this->get('/?tab=mylist');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: いいねをした商品が表示される
        $response->assertSee('いいねした商品');

        // 期待挙動: いいねしていない商品は表示されない
        $response->assertDontSee('いいねしていない商品');
    }

    /**
     * マイリストで購入済み商品に「Sold」ラベルが表示されることを検証する
     * 手順:
     *  1) ユーザーにログインをする
     *  2) マイリストページを開く
     *  3) 購入済み商品を確認する
     *
     * 期待挙動: 購入済み商品に「Sold」のラベルが表示される
     */
    public function test_mylist_shows_sold_label_for_purchased_items()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 他のユーザーを作成（出品者用）
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'category_names' => 'ファッション',
        ]);

        // 販売中の商品を作成（他のユーザーが出品）
        $availableItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => '販売中商品',
            'item_descriptions' => 'テスト商品の説明文です。',
        ]);

        // 購入済み商品を作成（is_sold = true、他のユーザーが出品）
        $purchasedItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => true,
            'item_names' => '購入済み商品',
            'item_descriptions' => 'テスト商品の説明文です。',
        ]);

        // カテゴリを関連付け
        $availableItem->categories()->attach($category->id);
        $purchasedItem->categories()->attach($category->id);

        // 両方の商品にいいねを作成
        ItemLike::create([
            'user_id' => $user->id,
            'item_id' => $availableItem->id,
        ]);

        ItemLike::create([
            'user_id' => $user->id,
            'item_id' => $purchasedItem->id,
        ]);

        // 1. ユーザーにログインをする
        $this->actingAs($user);

        // 2. マイリストページを開く
        $response = $this->get('/?tab=mylist');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 3. 購入済み商品を確認する
        // 期待挙動: 販売中の商品と購入済み商品の両方が表示される
        $response->assertSee('販売中商品');
        $response->assertSee('購入済み商品');

        // 期待挙動: 購入済み商品に「Sold」のラベルが表示される
        $response->assertSee('Sold');
    }

    /**
     * 未ログイン状態でマイリストページを開いて何も表示されないことを検証する
     * 手順:
     *  1) 未ログインで / トップ画面でマイリストページを開く
     *
     * 期待挙動: 何も表示されない
     */
    public function test_unauthenticated_user_sees_nothing_in_mylist()
    {
        // 未ログイン状態であることを確認
        $this->assertGuest();

        // 1. 未ログインで / トップ画面でマイリストページを開く
        $response = $this->get('/?tab=mylist');

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 何も表示されない（商品がない場合のメッセージが表示される）
        $response->assertSee('商品がありません。');
    }
}
