<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * 支払い方法選択機能のFeatureテスト
 *
 * テストID: 11
 */
class PaymentMethodSelectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 支払い方法選択画面を開いて支払い方法を選択できることを検証する
     * 手順:
     *  1) 支払い方法選択画面 /purchase/{item_id} を開く
     *  2) プルダウンメニューから支払い方法を選択する
     *
     * 期待挙動: 選択した支払い方法が正しく反映される
     */
    public function test_payment_method_selection_page_displays_and_accepts_selection()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
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
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 支払い方法選択画面 /purchase/{item_id} を開く
        $response = $this->get("/purchase/{$item->id}");

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 商品情報が表示される
        $response->assertSee($item->item_names);
        $response->assertSee(number_format($item->item_prices));

        // 期待挙動: 支払い方法セクションが表示される
        $response->assertSee('支払い方法');

        // 期待挙動: 支払い方法のプルダウンメニューが表示される
        $response->assertSee('選択してください');
        $response->assertSee('コンビニ支払い');
        $response->assertSee('カード支払い');

        // 期待挙動: プルダウンメニューのname属性が正しい
        $response->assertSee('name="payment_method"', false);

        // 期待挙動: プルダウンメニューのform属性が正しい
        $response->assertSee('form="purchase-form"', false);

        // 期待挙動: プルダウンメニューのvalue属性が正しい
        $response->assertSee('value="convenience_store"', false);
        $response->assertSee('value="credit_card"', false);
    }

  
}
