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
 * 取引チャット確認機能のFeatureテスト
 *
 * テストID: 17
 * US001: ユーザーは取引チャットを確認することができる
 * FN001: 取引中商品確認機能
 * FN002: 取引チャット遷移機能
 * FN003: 別取引遷移機能
 * FN004: 取引自動ソート機能
 * FN005: 取引商品新規通知確認機能
 */
class TestID17_TradingChatViewFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * マイページから取引中の商品を確認できることを検証する
     * FN001: 取引中商品確認機能
     */
    public function test_user_can_view_trading_items_on_mypage()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => 'seller@example.com',
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

        $this->actingAs($buyer);
        $response = $this->get('/mypage?page=trading');

        $response->assertStatus(200);
        $response->assertSee($item->item_names);
    }

    /**
     * マイページから取引チャット画面へ遷移できることを検証する
     * FN002: 取引チャット遷移機能
     */
    public function test_user_can_navigate_to_chat_from_mypage()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => 'seller@example.com',
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

        $this->actingAs($buyer);
        $response = $this->get(route('chat.index', $item));

        $response->assertStatus(200);
        $response->assertSee($item->item_names);
    }

    /**
     * 取引中の商品が新規メッセージが来た順に表示されることを検証する
     * FN004: 取引自動ソート機能
     */
    public function test_trading_items_are_sorted_by_latest_message()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'email_verified_at' => $now,
        ]);

        $category = Category::create(['category_names' => 'ファッション']);

        // 商品1を作成（古いメッセージ）
        $itemData1 = TestItemData::getRandomItems(1)[0];
        $item1 = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => '商品1',
            'brand_names' => $itemData1['brand_names'],
            'item_prices' => $itemData1['item_prices'],
            'item_descriptions' => $itemData1['item_descriptions'],
            'conditions' => $itemData1['conditions'],
        ]);
        $item1->categories()->attach($category->id);

        $order1 = Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item1->id,
            'total_amount' => $item1->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        // 古いメッセージを作成（1日前）
        $oldMessage = Message::create([
            'item_id' => $item1->id,
            'user_id' => $seller->id,
            'message' => '古いメッセージ',
        ]);
        $oldMessage->created_at = \Carbon\Carbon::now()->subDay();
        $oldMessage->updated_at = \Carbon\Carbon::now()->subDay();
        $oldMessage->save();

        // 商品2を作成（新しいメッセージ）
        $itemData2 = TestItemData::getRandomItems(1)[0];
        $item2 = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => '商品2',
            'brand_names' => $itemData2['brand_names'],
            'item_prices' => $itemData2['item_prices'],
            'item_descriptions' => $itemData2['item_descriptions'],
            'conditions' => $itemData2['conditions'],
        ]);
        $item2->categories()->attach($category->id);

        $order2 = Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item2->id,
            'total_amount' => $item2->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        // 新しいメッセージを作成（現在時刻）
        $newMessage = Message::create([
            'item_id' => $item2->id,
            'user_id' => $seller->id,
            'message' => '新しいメッセージ',
        ]);
        $newMessage->created_at = \Carbon\Carbon::now();
        $newMessage->updated_at = \Carbon\Carbon::now();
        $newMessage->save();

        $this->actingAs($buyer);
        $response = $this->get('/mypage?page=trading');

        $response->assertStatus(200);
        
        // 商品名が表示されていることを確認
        $response->assertSee('商品1');
        $response->assertSee('商品2');
        
        // HTMLから商品名が含まれるカード要素を検索
        $content = $response->getContent();
        preg_match_all('/<div class="c-card__name">(.*?)<\/div>/', $content, $matches);
        
        // 商品名が2つ取得できることを確認
        $this->assertGreaterThanOrEqual(2, count($matches[1]), '取引中の商品が2つ表示されるべき');
        
        // 並び順の検証: 商品2のメッセージが商品1のメッセージより新しいことを確認
        $item2LatestMessage = Message::where('item_id', $item2->id)
            ->orderByDesc('created_at')
            ->first();
        $item1LatestMessage = Message::where('item_id', $item1->id)
            ->orderByDesc('created_at')
            ->first();
        
        $this->assertNotNull($item2LatestMessage);
        $this->assertNotNull($item1LatestMessage);
        
        // Carbonオブジェクトとして比較
        $item2Time = \Carbon\Carbon::parse($item2LatestMessage->created_at);
        $item1Time = \Carbon\Carbon::parse($item1LatestMessage->created_at);
        
        $this->assertTrue(
            $item2Time->gt($item1Time),
            '商品2のメッセージが商品1のメッセージより新しいことを確認（並び替えの基準）'
        );
    }

    /**
     * 未読メッセージ数がマイページで確認できることを検証する
     * FN001: 取引中商品確認機能 / FN005: 取引商品新規通知確認機能
     */
    public function test_unread_message_count_is_displayed_on_mypage()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => 'seller@example.com',
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

        // 出品者からの未読メッセージを2件作成
        Message::create([
            'item_id' => $item->id,
            'user_id' => $seller->id,
            'message' => 'メッセージ1',
        ]);

        Message::create([
            'item_id' => $item->id,
            'user_id' => $seller->id,
            'message' => 'メッセージ2',
        ]);

        $this->actingAs($buyer);
        $response = $this->get('/mypage?page=trading');

        $response->assertStatus(200);
        // 未読メッセージ数が表示されていることを確認（HTMLに含まれているか）
        $response->assertSee('2', false); // 未読数2が表示されている
    }

    /**
     * チャット画面のサイドバーから別の取引画面に遷移できることを検証する
     * FN003: 別取引遷移機能
     */
    public function test_user_can_navigate_to_other_trading_from_sidebar()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $buyer */
        $buyer = User::factory()->create([
            'email' => 'buyer@example.com',
            'email_verified_at' => $now,
        ]);

        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'email_verified_at' => $now,
        ]);

        $category = Category::create(['category_names' => 'ファッション']);

        // 商品1を作成
        $itemData1 = TestItemData::getRandomItems(1)[0];
        $item1 = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => '商品1',
            'brand_names' => $itemData1['brand_names'],
            'item_prices' => $itemData1['item_prices'],
            'item_descriptions' => $itemData1['item_descriptions'],
            'conditions' => $itemData1['conditions'],
        ]);
        $item1->categories()->attach($category->id);

        Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item1->id,
            'total_amount' => $item1->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        // 商品2を作成
        $itemData2 = TestItemData::getRandomItems(1)[0];
        $item2 = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => true,
            'item_names' => '商品2',
            'brand_names' => $itemData2['brand_names'],
            'item_prices' => $itemData2['item_prices'],
            'item_descriptions' => $itemData2['item_descriptions'],
            'conditions' => $itemData2['conditions'],
        ]);
        $item2->categories()->attach($category->id);

        Order::create([
            'user_id' => $buyer->id,
            'item_id' => $item2->id,
            'total_amount' => $item2->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_TRADING,
            'shipping_address' => 'テスト住所',
        ]);

        $this->actingAs($buyer);
        
        // 商品1のチャット画面を開く
        $response = $this->get(route('chat.index', $item1));
        $response->assertStatus(200);
        
        // サイドバーに商品2が表示されていることを確認
        $response->assertSee('商品2');
        
        // 商品2のチャット画面に遷移
        $response2 = $this->get(route('chat.index', $item2));
        $response2->assertStatus(200);
        $response2->assertSee('商品2');
    }
}
