<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * 取引評価機能のFeatureテスト
 *
 * テストID: 21
 * US004: ユーザーは取引をしたユーザーを評価することができる
 * FN012: 取引後評価機能（購入者）
 * FN013: 取引後評価機能（出品者）
 * FN014: 取引後画面遷移
 */
class TestID21_TradingRatingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の取引データを作成
     */
    private function createTradingData($buyerEmail = 'buyer@example.com', $sellerEmail = 'seller@example.com')
    {
        $now = date('Y-m-d H:i:s');
        $buyer = User::factory()->create([
            'email' => $buyerEmail,
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => $sellerEmail,
            'email_verified_at' => $now,
        ]);

        $category = Category::create(['category_names' => 'ファッション']);
        $itemData = TestItemData::getRandomItems(1)[0];

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => $itemData['item_names'],
            'brand_names' => $itemData['brand_names'],
            'item_prices' => $itemData['item_prices'],
            'item_descriptions' => $itemData['item_descriptions'],
            'conditions' => $itemData['conditions'],
        ]);
        $item->categories()->attach($category->id);

        $order = Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        return compact('buyer', 'seller', 'item', 'order');
    }

    /**
     * 購入者が取引完了ボタンをクリックして評価できることを検証する
     * FN012: 取引後評価機能（購入者）
     * FN014: 取引後画面遷移
     */
    public function test_buyer_can_rate_seller_after_completing_trade()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $response = $this->post(route('chat.complete', $data['item']), [
            'rating' => 5,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('ratings', [
            'order_id' => $data['order']->id,
            'rater_id' => $data['buyer']->id,
            'rated_id' => $data['seller']->id,
            'rating' => 5,
        ]);
    }

    /**
     * 出品者が購入者を評価できることを検証する（購入者が評価済みの場合）
     * FN013: 取引後評価機能（出品者）
     * FN014: 取引後画面遷移
     */
    public function test_seller_can_rate_buyer_after_buyer_completed_trade()
    {
        $data = $this->createTradingData();
        
        // 購入者が評価を送信
        Rating::create([
            'order_id' => $data['order']->id,
            'rater_id' => $data['buyer']->id,
            'rated_id' => $data['seller']->id,
            'rating' => 4,
        ]);

        $this->actingAs($data['seller']);
        $response = $this->post(route('chat.seller-rate', $data['item']), [
            'rating' => 5,
        ]);

        $response->assertRedirect(route('items.index'));
        $this->assertDatabaseHas('ratings', [
            'order_id' => $data['order']->id,
            'rater_id' => $data['seller']->id,
            'rated_id' => $data['buyer']->id,
            'rating' => 5,
        ]);
    }

    /**
     * 評価を送信した後、商品一覧画面に遷移することを検証する
     * FN014: 取引後画面遷移
     */
    public function test_redirects_to_items_index_after_rating()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $response = $this->post(route('chat.complete', $data['item']), [
            'rating' => 3,
        ]);

        $response->assertRedirect(route('items.index'));
    }
}
