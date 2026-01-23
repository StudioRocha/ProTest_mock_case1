<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Item;
use App\Models\Order;
use App\Models\Message;
use App\Models\Category;
use Tests\Support\TestItemData;

/**
 * 取引チャット投稿機能のFeatureテスト
 *
 * テストID: 19
 * US002: ユーザーは取引チャットの投稿をすることができる
 * FN006: 取引チャット機能
 * FN007: バリデーション
 * FN008: エラーメッセージ表示
 * FN009: 入力情報保持機能
 */
class TestID19_ChatMessagePostFeatureTest extends TestCase
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
     * 購入者が本文のみでメッセージを投稿できることを検証する
     * FN006: 取引チャット機能
     */
    public function test_buyer_can_post_message_with_text_only()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);
        
        $response = $this->post(route('chat.store', $data['item']), [
            'message' => '購入者からのメッセージ',
        ]);

        $response->assertRedirect(route('chat.index', $data['item']));
        $this->assertDatabaseHas('messages', [
            'item_id' => $data['item']->id,
            'user_id' => $data['buyer']->id,
            'message' => '購入者からのメッセージ',
        ]);
    }

    /**
     * 出品者が本文のみでメッセージを投稿できることを検証する
     * FN006: 取引チャット機能
     */
    public function test_seller_can_post_message_with_text_only()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['seller']);
        
        $response = $this->post(route('chat.store', $data['item']), [
            'message' => '出品者からのメッセージ',
        ]);

        $response->assertRedirect(route('chat.index', $data['item']));
        $this->assertDatabaseHas('messages', [
            'item_id' => $data['item']->id,
            'user_id' => $data['seller']->id,
            'message' => '出品者からのメッセージ',
        ]);
    }

    /**
     * 本文と画像でメッセージを投稿できることを検証する
     * FN006: 取引チャット機能
     */
    public function test_user_can_post_message_with_text_and_image()
    {
        Storage::fake('public');
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);
        
        $image = UploadedFile::fake()->create('test.png', 100, 'image/png');
        $response = $this->post(route('chat.store', $data['item']), [
            'message' => '画像付きメッセージ',
            'image' => $image,
        ]);

        $response->assertRedirect(route('chat.index', $data['item']));
        $message = Message::where('item_id', $data['item']->id)
            ->where('user_id', $data['buyer']->id)
            ->first();
        $this->assertNotNull($message);
        $this->assertNotNull($message->image_path);
        $this->assertTrue(Storage::disk('public')->exists($message->image_path));
    }

    /**
     * 本文が未入力の場合のバリデーションエラーを検証する
     * FN007: バリデーション / FN008: エラーメッセージ表示
     */
    public function test_validation_error_when_message_is_empty()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $response = $this->post(route('chat.store', $data['item']), [
            'message' => '',
        ]);

        $response->assertSessionHasErrors('message');
        $response->assertSessionHasErrors(['message' => '本文を入力してください']);
        $this->assertDatabaseMissing('messages', [
            'item_id' => $data['item']->id,
            'user_id' => $data['buyer']->id,
        ]);
    }

    /**
     * 本文が401文字以上の場合のバリデーションエラーを検証する
     * FN007: バリデーション / FN008: エラーメッセージ表示
     */
    public function test_validation_error_when_message_exceeds_400_characters()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $response = $this->post(route('chat.store', $data['item']), [
            'message' => str_repeat('a', 401),
        ]);

        $response->assertSessionHasErrors('message');
        $response->assertSessionHasErrors(['message' => '本文は400文字以内で入力してください']);
    }

    /**
     * 画像が.pngまたは.jpeg形式以外の場合のバリデーションエラーを検証する
     * FN007: バリデーション / FN008: エラーメッセージ表示
     */
    public function test_validation_error_when_image_is_not_png_or_jpeg()
    {
        Storage::fake('public');
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        $gifImage = UploadedFile::fake()->create('test.gif', 100, 'image/gif');
        $response = $this->post(route('chat.store', $data['item']), [
            'message' => 'テストメッセージ',
            'image' => $gifImage,
        ]);

        $response->assertSessionHasErrors('image');
        $response->assertSessionHasErrors(['image' => '「.png」または「.jpeg」形式でアップロードしてください']);
    }

    /**
     * 入力情報保持機能を検証する（本文のみ）
     * FN009: 入力情報保持機能
     */
    public function test_input_message_is_persisted_in_session()
    {
        $data = $this->createTradingData();
        $this->actingAs($data['buyer']);

        // セッションにメッセージを保存
        session(["chat_message_{$data['item']->id}" => '保存されたメッセージ']);

        // チャット画面を開く
        $response = $this->get(route('chat.index', $data['item']));

        $response->assertStatus(200);
        // 保存されたメッセージが表示されることを確認
        $response->assertSee('保存されたメッセージ', false);
    }
}
