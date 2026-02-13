<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\ItemLike;
use Tests\Support\TestItemData;

/**
 * 商品いいね機能のFeatureテスト
 * 
 * テストID: 8
 */
class ItemLikeFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーが商品にいいねできることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品詳細ページを開く
     *  3) いいねアイコンを押下
     *
     * 期待挙動: いいねした商品として登録され、いいね合計値が増加表示される
     */
    public function test_logged_in_user_can_like_item()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $itemOwner */
        $itemOwner = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        // カテゴリーを作成
        $category = Category::create([
            'category_names' => 'ファッション',
        ]);

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 商品を作成
        $item = Item::factory()->create([
            'user_id' => $itemOwner->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
            'like_counts' => 5, // 初期いいね数
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);

        // 初期状態のいいね数を確認
        $initialLikeCount = $item->like_counts;
        $this->assertEquals(5, $initialLikeCount);

        // 3. いいねアイコンを押下
        $likeResponse = $this->post("/item/{$item->id}/like");

        // 期待挙動: リダイレクトされる
        $likeResponse->assertStatus(302);
        $likeResponse->assertRedirect("/item/{$item->id}");

        // 期待挙動: いいねした商品として登録される
        $this->assertDatabaseHas('item_likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: いいね合計値が増加表示される
        $item->refresh(); // データベースから最新の値を取得
        $this->assertEquals(6, $item->like_counts); // 5 + 1 = 6

        // 期待挙動: いいねボタンがアクティブ状態になる
        $updatedDetailResponse = $this->get("/item/{$item->id}");
        $updatedDetailResponse->assertStatus(200);
        $updatedDetailResponse->assertSee('★'); // アクティブ状態の星
        $updatedDetailResponse->assertSee('6'); // 更新されたいいね数
    }

    
    /**
     * いいねアイコンの色変化を検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品詳細ページを開く
     *  3) いいねアイコンを押下
     *
     * 期待挙動: いいねアイコンが押下された状態では色が変化する
     */
    public function test_like_icon_color_changes_when_pressed()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $itemOwner */
        $itemOwner = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        // カテゴリーを作成
        $category = Category::create([
            'category_names' => 'インテリア',
        ]);

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 商品を作成
        $item = Item::factory()->create([
            'user_id' => $itemOwner->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
            'like_counts' => 0,
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);

        // 初期状態のいいねボタンを確認（非アクティブ状態）
        $detailResponse->assertSee('☆'); // 非アクティブ状態の星
        $detailResponse->assertSee('0'); // 初期いいね数

        // 3. いいねアイコンを押下
        $likeResponse = $this->post("/item/{$item->id}/like");

        // 期待挙動: リダイレクトされる
        $likeResponse->assertStatus(302);
        $likeResponse->assertRedirect("/item/{$item->id}");

        // 期待挙動: いいねが登録される
        $this->assertDatabaseHas('item_likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: いいね数が増加する
        $item->refresh();
        $this->assertEquals(1, $item->like_counts);

        // 期待挙動: いいねアイコンが押下された状態で色が変化する
        $updatedDetailResponse = $this->get("/item/{$item->id}");
        $updatedDetailResponse->assertStatus(200);
        $updatedDetailResponse->assertSee('★'); // アクティブ状態の星（色変化）
        $updatedDetailResponse->assertSee('1'); // 更新されたいいね数

        // 期待挙動: アクティブ状態のCSSクラスが適用される
        $updatedDetailResponse->assertSee('p-like__btn--active'); // アクティブ状態のCSSクラス
    }

    /**
     * ログインユーザーがいいねを取り消せることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品詳細ページを開く
     *  3) いいねアイコンを押下（いいね取り消し）
     *
     * 期待挙動: いいねが取り消され、いいね合計値が減少表示される
     */
    public function test_logged_in_user_can_unlike_item()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        /** @var \App\Models\User $itemOwner */
        $itemOwner = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        // カテゴリーを作成
        $category = Category::create([
            'category_names' => '家電',
        ]);

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 商品を作成
        $item = Item::factory()->create([
            'user_id' => $itemOwner->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
            'like_counts' => 3, // 初期いいね数
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 事前にいいねを登録
        ItemLike::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);

        // 初期状態のいいね数を確認
        $initialLikeCount = $item->like_counts;
        $this->assertEquals(3, $initialLikeCount);

        // 3. いいねアイコンを押下（いいね取り消し）
        $unlikeResponse = $this->post("/item/{$item->id}/like");

        // 期待挙動: リダイレクトされる
        $unlikeResponse->assertStatus(302);
        $unlikeResponse->assertRedirect("/item/{$item->id}");

        // 期待挙動: いいねが取り消される
        $this->assertDatabaseMissing('item_likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: いいね合計値が減少表示される
        $item->refresh(); // データベースから最新の値を取得
        $this->assertEquals(2, $item->like_counts); // 3 - 1 = 2

        // 期待挙動: いいねボタンが非アクティブ状態になる
        $updatedDetailResponse = $this->get("/item/{$item->id}");
        $updatedDetailResponse->assertStatus(200);
        $updatedDetailResponse->assertSee('☆'); // 非アクティブ状態の星
        $updatedDetailResponse->assertSee('2'); // 更新されたいいね数
    }


}
