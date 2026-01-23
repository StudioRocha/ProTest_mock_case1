<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * 商品詳細情報取得機能のFeatureテスト
 * 
 * テストID: 7
 */
class ItemDetailFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品詳細ページを開いてすべての情報が表示されることを検証する
     * 手順:
     *  1) 商品詳細ページを開く
     *
     * 期待挙動: すべての情報が商品詳細ページに表示されている
     */
    public function test_item_detail_page_displays_all_information()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 本番で使用するカテゴリーを作成
        $categoryNames = ['ファッション','家電','インテリア','レディース','メンズ','コスメ','本','ゲーム','スポーツ','キッチン','ハンドメイド','アクセサリー','おもちゃ','ベビー・キッズ'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $categories[] = Category::create(['category_names' => $name]);
        }

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 商品を作成
        $item = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
        ]);

        // カテゴリを関連付け（ランダムに2つ選択）
        $randomCategories = collect($categories)->random(2);
        $item->categories()->attach($randomCategories->pluck('id'));

        // 1. 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 商品名が表示される
        $response->assertSee($item->item_names);

        // 期待挙動: ブランド名が表示される
        if (!empty($item->brand_names)) {
            $response->assertSee($item->brand_names);
        }

        // 期待挙動: 価格が表示される
        $response->assertSee(number_format($item->item_prices));

        // 期待挙動: 商品説明が表示される
        $response->assertSee($item->item_descriptions);

        // 期待挙動: 商品状態が表示される
        $conditions = [1 => '良好', 2 => '目立った傷や汚れ無し', 3 => 'やや傷や汚れあり', 4 => '状態が悪い'];
        $conditionText = $conditions[$item->conditions] ?? '不明';
        $response->assertSee($conditionText);

        // 期待挙動: カテゴリーが表示される
        foreach ($randomCategories as $category) {
            $response->assertSee($category->category_names);
        }

        // 期待挙動: 商品画像が表示される（画像パスが含まれている）
        $response->assertSee($item->item_image_paths);
    }

    /**
     * 商品詳細ページで複数カテゴリーが表示されることを検証する
     * 手順:
     *  1) 商品詳細ページを開く
     *
     * 期待挙動: 複数選択されたカテゴリが商品詳細ページに表示されている
     */
    public function test_item_detail_page_displays_multiple_categories()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 本番で使用するカテゴリーを作成
        $categoryNames = ['ファッション','家電','インテリア','レディース','メンズ','コスメ','本','ゲーム','スポーツ','キッチン','ハンドメイド','アクセサリー','おもちゃ','ベビー・キッズ'];
        $categories = [];
        foreach ($categoryNames as $name) {
            $categories[] = Category::create(['category_names' => $name]);
        }

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 商品を作成
        $item = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
        ]);

        // カテゴリを関連付け（3つ選択して複数カテゴリーを明確にテスト）
        $selectedCategories = collect($categories)->random(3);
        $item->categories()->attach($selectedCategories->pluck('id'));

        // 1. 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 複数選択されたカテゴリが表示される
        foreach ($selectedCategories as $category) {
            $response->assertSee($category->category_names);
        }

        // 期待挙動: カテゴリーセクションが存在する
        $response->assertSee('カテゴリー');

        // 期待挙動: 選択されたカテゴリーの数が正しい（3つ）
        $this->assertEquals(3, $selectedCategories->count());
    }

}
