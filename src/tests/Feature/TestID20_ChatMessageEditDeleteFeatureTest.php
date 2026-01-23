<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Message;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * 取引チャット編集・削除機能のFeatureテスト
 *
 * テストID: 20
 * US003: ユーザーは取引チャットの編集、削除をすることができる
 * FN010: メッセージ編集機能
 * FN011: メッセージ削除機能
 */
class TestID20_ChatMessageEditDeleteFeatureTest extends TestCase
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

        Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        return compact('buyer', 'seller', 'item');
    }

    /**
     * 自分のメッセージを編集できることを検証する
     * FN010: メッセージ編集機能
     */
    public function test_user_can_edit_own_message()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $message = Message::create([
            'item_id' => $data['item']->id,
            'user_id' => $data['buyer']->id,
            'message' => '元のメッセージ',
        ]);

        $response = $this->put(route('chat.update', ['item' => $data['item'], 'message' => $message]), [
            'message' => '編集後のメッセージ',
        ]);

        $response->assertRedirect(route('chat.index', $data['item']));
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message' => '編集後のメッセージ',
        ]);
    }

    /**
     * 自分のメッセージを削除できることを検証する
     * FN011: メッセージ削除機能
     */
    public function test_user_can_delete_own_message()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $message = Message::create([
            'item_id' => $data['item']->id,
            'user_id' => $data['buyer']->id,
            'message' => '削除するメッセージ',
        ]);

        $response = $this->delete(route('chat.destroy', ['item' => $data['item'], 'message' => $message]));

        $response->assertRedirect(route('chat.index', $data['item']));
        // ソフトデリートなので、deleted_atが設定される
        $this->assertSoftDeleted('messages', [
            'id' => $message->id,
        ]);
    }

    /**
     * 他人のメッセージは編集できないことを検証する
     * FN010: メッセージ編集機能
     */
    public function test_user_cannot_edit_other_users_message()
    {
        $data = $this->createTradingData();
        
        // 出品者のメッセージを作成
        $message = Message::create([
            'item_id' => $data['item']->id,
            'user_id' => $data['seller']->id,
            'message' => '出品者のメッセージ',
        ]);

        // 購入者が編集を試みる
        $this->actingAs($data['buyer']);
        $response = $this->put(route('chat.update', ['item' => $data['item'], 'message' => $message]), [
            'message' => '編集しようとしたメッセージ',
        ]);

        $response->assertRedirect(route('chat.index', $data['item']));
        // メッセージは変更されていない
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message' => '出品者のメッセージ',
        ]);
    }

    /**
     * 他人のメッセージは削除できないことを検証する
     * FN011: メッセージ削除機能
     */
    public function test_user_cannot_delete_other_users_message()
    {
        $data = $this->createTradingData();
        
        // 出品者のメッセージを作成
        $message = Message::create([
            'item_id' => $data['item']->id,
            'user_id' => $data['seller']->id,
            'message' => '出品者のメッセージ',
        ]);

        // 購入者が削除を試みる
        $this->actingAs($data['buyer']);
        $response = $this->delete(route('chat.destroy', ['item' => $data['item'], 'message' => $message]));

        $response->assertRedirect(route('chat.index', $data['item']));
        // メッセージは削除されていない（deleted_atがnull）
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'deleted_at' => null,
        ]);
    }
}
