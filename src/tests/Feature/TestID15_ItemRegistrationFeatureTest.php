<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;


/**
 * 出品商品情報登録機能のFeatureテスト
 *
 * テストID: 15
 */
class ItemRegistrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品出品画面で必要な情報が正しく保存されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品出品画面を開く
     *  3) 各項目に適切な情報を入力して保存する
     *
     * 期待挙動: 商品出品画面にて必要な情報が保存できること（カテゴリ、商品の状態、商品名、ブランド名、商品の説明、販売価格）
     * 各項目が正しく保存されている
     */
    public function test_item_registration_saves_all_required_information()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'seller@example.com',
            'email_verified_at' => now(),
        ]);

        // カテゴリーを作成
        $category1 = Category::create([
            'category_names' => 'ファッション',
        ]);
        $category2 = Category::create([
            'category_names' => '家電',
        ]);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品出品画面を開く
        $response = $this->get('/sell');
        $response->assertStatus(200);

        // 3. 各項目に適切な情報を入力して保存する
        $itemData = [
            'item_name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'item_price' => 15000,
            'item_description' => 'これはテスト用の商品説明です。詳細な説明をここに記載します。',
            'condition' => 1, // 良好
            'category_ids' => [$category1->id, $category2->id], // 複数カテゴリ選択
        ];

        // テスト用の画像ファイルを作成
        $testImage = \Illuminate\Http\UploadedFile::fake()->create('test-item.jpg', 1024, 'image/jpeg');

        $response = $this->post('/sell', array_merge($itemData, [
            'item_image' => $testImage,
        ]));

        // 期待挙動: 商品詳細ページにリダイレクトされる
        $response->assertStatus(302);
        $response->assertRedirect();

        // 期待挙動: データベースに商品が正しく保存されている
        $this->assertDatabaseHas('items', [
            'user_id' => $user->id,
            'item_names' => 'テスト商品',
            'brand_names' => 'テストブランド',
            'item_prices' => 15000,
            'item_descriptions' => 'これはテスト用の商品説明です。詳細な説明をここに記載します。',
            'conditions' => 1,
            'is_sold' => false,
        ]);

        // 期待挙動: 商品が作成されている
        $item = Item::where('item_names', 'テスト商品')->first();
        $this->assertNotNull($item);

        // 期待挙動: カテゴリが正しく関連付けられている
        $this->assertTrue($item->categories->contains($category1));
        $this->assertTrue($item->categories->contains($category2));
        $this->assertEquals(2, $item->categories->count());

        // 期待挙動: 商品詳細ページで情報が正しく表示される
        $detailResponse = $this->get("/item/{$item->id}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('テスト商品');
        $detailResponse->assertSee('テストブランド');
        $detailResponse->assertSee('15,000');
        $detailResponse->assertSee('これはテスト用の商品説明です。詳細な説明をここに記載します。');
        $detailResponse->assertSee('ファッション');
        $detailResponse->assertSee('家電');
    }
}
