<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Order;
use App\Services\StripeService;
use Tests\Support\TestItemData;
use Mockery;

/**
 * 商品購入機能のFeatureテスト
 *
 * テストID: 10
 */
class ItemPurchaseFeatureTest extends TestCase
{
    use RefreshDatabase;
    
    // テスト用Stripe定数
    private const TEST_CHECKOUT_URL = 'https://checkout.stripe.com/pay/test_session_id';
    private const TEST_SESSION_ID = 'test_session_id';

    protected function setUp(): void
    {
        parent::setUp();
        
        // StripeServiceをモック
        $this->mockStripeService();
    }


    /**
     * StripeServiceをモックして、テスト環境でStripe APIを呼び出さないようにする
     */
    private function mockStripeService()
    {
        $mockStripeService = Mockery::mock(StripeService::class);
        
        // createCheckoutSessionメソッドをモック
        $mockStripeService->shouldReceive('createCheckoutSession')
            ->andReturn((object) [
                'url' => self::TEST_CHECKOUT_URL,
                'id' => self::TEST_SESSION_ID
            ]);
        
        // retrieveSessionメソッドをモック
        $mockStripeService->shouldReceive('retrieveSession')
            ->andReturn((object) [
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'metadata' => (object) [
                    'user_id' => 1,
                    'item_id' => 1,
                    'shipping_address' => 'テスト住所'
                ],
                'payment_method_types' => ['card']
            ]);
        
        // サービスコンテナにモックを登録
        $this->app->instance(StripeService::class, $mockStripeService);
    }

    /**
     * ログインユーザーが商品を購入できることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品購入画面 /purchase を開く
     *  3) 商品を選択して「購入する」ボタンを押下
     *
     * 期待挙動: 購入が完了する
     */
    public function test_logged_in_user_can_purchase_item()
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
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品購入画面 /purchase を開く
        $purchaseFormResponse = $this->get("/purchase/{$item->id}");
        $purchaseFormResponse->assertStatus(200);

        // 購入画面の内容を確認
        $purchaseFormResponse->assertSee($item->item_names); // 商品名
        $purchaseFormResponse->assertSee(number_format($item->item_prices)); // 価格
        $purchaseFormResponse->assertSee('購入する'); // 購入ボタン

        // 3. 商品を選択して「購入する」ボタンを押下（Stripe決済セッション作成）
        $stripeResponse = $this->post("/item/{$item->id}/stripe/checkout", [
            'item_id' => $item->id,
            'payment_method' => 'credit_card',
            'shipping_address' => 'テスト住所',
        ]);


        // 期待挙動: Stripe決済画面にリダイレクトされる
        $stripeResponse->assertStatus(302);
        $stripeResponse->assertRedirect(); // StripeのURLにリダイレクト

        // 期待挙動: リダイレクト先がStripe決済画面であることを確認
        $redirectUrl = $stripeResponse->headers->get('Location');
        $this->assertStringContainsString('checkout.stripe.com', $redirectUrl);
        $this->assertEquals(self::TEST_CHECKOUT_URL, $redirectUrl);

        // 期待挙動: この時点ではOrderレコードは作成されていない（Stripe決済完了後にWebhookで作成される）
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: 商品はまだ売却済みになっていない（Stripe決済完了後に更新される）
        $item->refresh();
        $this->assertEquals(0, $item->is_sold); // 0 = 未売却
    }

    /**
     * 購入完了後に商品一覧で「Sold」表示されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品購入画面を開く
     *  3) 商品を選択して「購入する」ボタンを押下
     *  4) 商品一覧画面を表示する
     *
     * 期待挙動: 購入した商品が「Sold」として表示されている
     */
    public function test_purchased_item_shows_as_sold_in_item_list()
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
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $purchaseFormResponse = $this->get("/purchase/{$item->id}");
        $purchaseFormResponse->assertStatus(200);

        // 3. 商品を選択して「購入する」ボタンを押下（Stripe決済セッション作成）
        $stripeResponse = $this->post("/item/{$item->id}/stripe/checkout", [
            'item_id' => $item->id,
            'payment_method' => 'credit_card',
            'shipping_address' => 'テスト住所',
        ]);

        // 期待挙動: Stripe決済画面にリダイレクトされる
        $stripeResponse->assertStatus(302);
        $stripeResponse->assertRedirect();

        // 期待挙動: リダイレクト先がStripe決済画面であることを確認
        $redirectUrl = $stripeResponse->headers->get('Location');
        $this->assertStringContainsString('checkout.stripe.com', $redirectUrl);
        $this->assertEquals(self::TEST_CHECKOUT_URL, $redirectUrl);

        // 決済完了をシミュレート（実際のStripe決済完了後の処理）
        // Orderレコードを作成
        Order::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'shipping_address' => 'テスト住所',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // 商品を売却済みにマーク
        $item->update(['is_sold' => true]);

        // 4. 商品一覧画面を表示する
        $itemListResponse = $this->get('/');

        // 期待挙動: 商品一覧ページが正常に表示される
        $itemListResponse->assertStatus(200);

        // 期待挙動: 購入した商品が「Sold」として表示されている
        $itemListResponse->assertSee('Sold');
        $itemListResponse->assertSee($item->item_names);

        // 期待挙動: Orderレコードが作成されている
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // 期待挙動: 商品が売却済みになっている
        $item->refresh();
        $this->assertEquals(1, $item->is_sold); // 1 = 売却済み
    }

    /**
     * 購入完了後にプロフィール画面で購入商品一覧に追加されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品購入画面を開く
     *  3) 商品を選択して「購入する」ボタンを押下
     *  4) プロフィール画面を表示する
     *
     * 期待挙動: 購入した商品がプロフィールの購入した商品一覧に追加されている
     */
    public function test_purchased_item_appears_in_profile_purchase_list()
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
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $purchaseFormResponse = $this->get("/purchase/{$item->id}");
        $purchaseFormResponse->assertStatus(200);

        // 3. 商品を選択して「購入する」ボタンを押下（Stripe決済セッション作成）
        $stripeResponse = $this->post("/item/{$item->id}/stripe/checkout", [
            'item_id' => $item->id,
            'payment_method' => 'credit_card',
            'shipping_address' => 'テスト住所',
        ]);

        // 期待挙動: Stripe決済画面にリダイレクトされる
        $stripeResponse->assertStatus(302);
        $stripeResponse->assertRedirect();

        // 期待挙動: リダイレクト先がStripe決済画面であることを確認
        $redirectUrl = $stripeResponse->headers->get('Location');
        $this->assertStringContainsString('checkout.stripe.com', $redirectUrl);
        $this->assertEquals(self::TEST_CHECKOUT_URL, $redirectUrl);

        // 決済完了をシミュレート（実際のStripe決済完了後の処理）
        // Orderレコードを作成
        Order::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'shipping_address' => 'テスト住所',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // 商品を売却済みにマーク
        $item->update(['is_sold' => true]);

        // 4. プロフィール画面を表示する
        $profileResponse = $this->get('/mypage?page=buy');

        // 期待挙動: プロフィールページが正常に表示される
        $profileResponse->assertStatus(200);

        // 期待挙動: 購入した商品がプロフィールの購入した商品一覧に表示されている
        $profileResponse->assertSee($item->item_names);
        $profileResponse->assertSee('Sold');

        // 期待挙動: Orderレコードが作成されている
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        // 期待挙動: 商品が売却済みになっている
        $item->refresh();
        $this->assertEquals(1, $item->is_sold); // 1 = 売却済み
    }

    /**
     * コンビニ決済の場合、payment_statusがPAYMENT_PENDINGでOrderが作成されることを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 商品購入画面を開く
     *  3) コンビニ決済を選択して「購入する」ボタンを押下
     *
     * 期待挙動: Orderレコードが作成され、payment_statusがPAYMENT_PENDINGになる
     */
    public function test_convenience_store_payment_creates_order_with_payment_pending_status()
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
        ]);

        // カテゴリを関連付け
        $item->categories()->attach($category->id);

        // 1. ユーザーにログインする
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $purchaseFormResponse = $this->get("/purchase/{$item->id}");
        $purchaseFormResponse->assertStatus(200);

        // 3. コンビニ決済を選択して「購入する」ボタンを押下（Ajaxリクエスト）
        $stripeResponse = $this->postJson("/item/{$item->id}/stripe/checkout", [
            'item_id' => $item->id,
            'payment_method' => 'convenience_store',
            'shipping_address' => 'テスト住所',
        ]);

        // 期待挙動: JSONレスポンスが返される
        $stripeResponse->assertStatus(200);
        $stripeResponse->assertJsonStructure(['redirect_url']);

        // 期待挙動: Orderレコードが作成されている
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'payment_method' => 'convenience_store',
            'payment_status' => Order::PAYMENT_STATUS_PAYMENT_PENDING,
            'trade_status' => Order::TRADE_STATUS_TRADING,
        ]);

        // 期待挙動: 商品が売却済みになっている（コンビニ決済の場合は在庫予約）
        $item->refresh();
        $this->assertEquals(1, $item->is_sold); // 1 = 売却済み

        // 期待挙動: フラッシュメッセージがセッションに保存されている
        $this->assertTrue(session()->has('success'));
        $this->assertStringContainsString('コンビニ支払', session('success'));
    }
}
