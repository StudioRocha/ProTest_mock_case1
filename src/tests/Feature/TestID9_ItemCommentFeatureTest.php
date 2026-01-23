<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * コメント送信機能のFeatureテスト
 * 
 * テストID: 9
 */
class ItemCommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーが商品にコメントを送信できることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) コメントを入力する
     *  3) コメントボタンを押す
     *
     * 期待挙動: コメントが保存され、コメント数が増加する
     */
    public function test_logged_in_user_can_send_comment()
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
            'comment_counts' => 0, // 初期コメント数
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);

        // 初期状態のコメント数を確認
        $initialCommentCount = $item->comment_counts;
        $this->assertEquals(0, $initialCommentCount);

        // 2. コメントを入力する
        $commentText = 'とても良い商品ですね！購入を検討しています。';

        // 3. コメントボタンを押す
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'comment_body' => $commentText,
        ]);

        // 期待挙動: リダイレクトされる
        $commentResponse->assertStatus(302);
        $commentResponse->assertRedirect("/item/{$item->id}");

        // 期待挙動: コメントが保存される
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment_body' => $commentText,
        ]);

        // 期待挙動: コメント数が増加する
        $item->refresh(); // データベースから最新の値を取得
        $this->assertEquals(1, $item->comment_counts); // 0 + 1 = 1

        // 期待挙動: コメントが商品詳細ページに表示される
        $updatedDetailResponse = $this->get("/item/{$item->id}");
        $updatedDetailResponse->assertStatus(200);
        $updatedDetailResponse->assertSee($commentText); // 送信したコメント
        $updatedDetailResponse->assertSee('1'); // 更新されたコメント数
        $updatedDetailResponse->assertSee('コメント(1)'); // コメントセクションのタイトル
    }

  

    /**
     * 未ログインユーザーがコメントを送信できないことを検証する
     * 手順:
     *  1) 商品詳細ページを開く
     *  2) コメントを入力する
     *  3) コメントボタンを押す
     *
     * 期待挙動: ログインページにリダイレクトされる
     */
    public function test_unauthenticated_user_cannot_send_comment()
    {
        // テスト用データを作成
        /** @var \App\Models\User $itemOwner */
        $itemOwner = User::factory()->create([
            'email' => 'owner@example.com',
            'email_verified_at' => now(),
        ]);

        // カテゴリーを作成
        $category = Category::create([
            'category_names' => 'スポーツ',
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
            'comment_counts' => 0,
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. 商品詳細ページを開く
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);

        // 2. コメントを入力する
        $commentText = 'この商品について質問があります。';

        // 3. コメントボタンを押す
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'comment_body' => $commentText,
        ]);

        // 期待挙動: ログインページにリダイレクトされる
        $commentResponse->assertStatus(302);
        $commentResponse->assertRedirect('/login');

        // 期待挙動: コメントが保存されない
        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
        ]);

        // 期待挙動: コメント数が変更されない
        $item->refresh();
        $this->assertEquals(0, $item->comment_counts);
    }

      /**
     * 空のコメントを送信できないことを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 空のコメントを入力する
     *  3) コメントボタンを押す
     *
     * 期待挙動: バリデーションエラーが表示され、コメントが保存されない
     */
    public function test_cannot_send_empty_comment()
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
            'comment_counts' => 1,
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 空のコメントを入力する
        $emptyComment = '';

        // 3. コメントボタンを押す
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'comment_body' => $emptyComment,
        ]);

        // 期待挙動: バリデーションエラーが発生する
        $commentResponse->assertStatus(302);
        $commentResponse->assertSessionHasErrors('comment_body');

        // 期待挙動: コメントが保存されない
        $this->assertDatabaseMissing('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: コメント数が変更されない
        $item->refresh();
        $this->assertEquals(1, $item->comment_counts);
    }

    /**
     * 255文字以上のコメントを送信できないことを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 255文字以上のコメントを入力する
     *  3) コメントボタンを押す
     *
     * 期待挙動: コメントが255字以上の場合、バリデーションメッセージが表示される
     */
    public function test_cannot_send_comment_over_255_characters()
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
            'category_names' => 'ゲーム',
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
            'comment_counts' => 0,
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 255文字以上のコメントを入力する
        $longComment = str_repeat('あ', 256); // 256文字（255文字を超える）

        // 3. コメントボタンを押す
        $commentResponse = $this->post("/item/{$item->id}/comment", [
            'comment_body' => $longComment,
        ]);

        // 期待挙動: バリデーションエラーが発生する
        $commentResponse->assertStatus(302);
        $commentResponse->assertSessionHasErrors('comment_body');

        // 期待挙動: コメントが保存されない
        $this->assertDatabaseMissing('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: コメント数が変更されない
        $item->refresh();
        $this->assertEquals(0, $item->comment_counts);

        // 期待挙動: バリデーションメッセージが表示される
        $this->assertTrue(strlen($longComment) > 255);
    }
}
