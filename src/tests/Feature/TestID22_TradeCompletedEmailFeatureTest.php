<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Rating;
use App\Models\Category;
use App\Mail\TradeCompletedMail;
use Tests\Support\TestItemData;

/**
 * 取引完了メール送信機能のFeatureテスト
 *
 * テストID: 22
 * US005: 出品ユーザーは取引完了をメールで確認することができる
 * FN016: メール送信機能
 */
class TestID22_TradeCompletedEmailFeatureTest extends TestCase
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
     * 購入者が取引を完了すると、出品者宛に通知メールが送信されることを検証する
     * FN016: メール送信機能
     */
    public function test_email_is_sent_to_seller_when_buyer_completes_trade()
    {
        Mail::fake();
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $this->post(route('chat.complete', $data['item']), [
            'rating' => 5,
        ]);

        // 出品者宛にメールが送信されたことを確認
        Mail::assertSent(TradeCompletedMail::class, function ($mail) use ($data) {
            return $mail->hasTo($data['seller']->email);
        });
    }
}
