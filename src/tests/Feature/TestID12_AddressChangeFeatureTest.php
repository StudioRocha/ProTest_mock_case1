<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use App\Models\Category;
use App\Models\Profile;
use Tests\Support\TestItemData;

/**
 * 配送先変更機能のFeatureテスト
 *
 * テストID: 12
 */
class AddressChangeFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 配送先変更機能が正常に動作することを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 送付先住所変更画面で住所を登録する
     *  3) 商品購入画面を再度開く
     *
     * 期待挙動: 登録した住所が商品購入画面に正しく反映される
     */
    public function test_address_change_functionality_works_correctly()
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

        // ユーザーのプロフィールを作成（初期住所）
        $profile = Profile::create([
            'user_id' => $user->id,
            'usernames' => 'テストユーザー',
            'postal_codes' => '100-0001',
            'addresses' => '東京都千代田区千代田1-1-1',
            'building_names' => 'テストマンション101号',
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

        // 2. 商品購入画面を開く（初期住所が表示される）
        $purchaseResponse = $this->get("/purchase/{$item->id}");
        $purchaseResponse->assertStatus(200);

        // 初期住所が表示されることを確認
        $purchaseResponse->assertSee('100-0001');
        $purchaseResponse->assertSee('東京都千代田区千代田1-1-1');
        $purchaseResponse->assertSee('テストマンション101号');

        // 3. 送付先住所変更画面を開く
        $addressChangeResponse = $this->get("/purchase/address/{$item->id}");
        $addressChangeResponse->assertStatus(200);

        // 住所変更画面が正しく表示されることを確認
        $addressChangeResponse->assertSee('住所の変更');
        $addressChangeResponse->assertSee('郵便番号');
        $addressChangeResponse->assertSee('住所');
        $addressChangeResponse->assertSee('建物名');

        // 4. 新しい住所を登録する
        $newAddressData = [
            'postal_code' => '150-0002',
            'address' => '東京都渋谷区恵比寿2-2-2',
            'building_name' => '恵比寿タワー202号',
        ];

        $updateResponse = $this->post("/purchase/address/{$item->id}", $newAddressData);

        // 期待挙動: 商品購入画面にリダイレクトされる
        $updateResponse->assertStatus(302);
        $updateResponse->assertRedirect("/purchase/{$item->id}");

        // 期待挙動: 成功メッセージが表示される
        $updateResponse->assertSessionHas('success', '送付先住所を変更しました。');

        // 5. 商品購入画面を再度開く
        $updatedPurchaseResponse = $this->get("/purchase/{$item->id}");
        $updatedPurchaseResponse->assertStatus(200);

        // 期待挙動: 登録した新しい住所が商品購入画面に正しく反映される
        $updatedPurchaseResponse->assertSee('150-0002');
        $updatedPurchaseResponse->assertSee('東京都渋谷区恵比寿2-2-2');
        $updatedPurchaseResponse->assertSee('恵比寿タワー202号');

        // 期待挙動: 古い住所は表示されない
        $updatedPurchaseResponse->assertDontSee('100-0001');
        $updatedPurchaseResponse->assertDontSee('東京都千代田区千代田1-1-1');
        $updatedPurchaseResponse->assertDontSee('テストマンション101号');
    }

    /**
     * 配送先変更後に商品を購入した際に正しく送付先住所が紐づくことを検証する
     * 手順:
     *  1) ユーザーにログインする
     *  2) 送付先住所変更画面で住所を登録する
     *  3) 商品を購入する
     *
     * 期待挙動: 正しく送付先住所が紐づいている
     */
    public function test_address_change_and_purchase_with_correct_shipping_address()
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

        // ユーザーのプロフィールを作成（初期住所）
        $profile = Profile::create([
            'user_id' => $user->id,
            'usernames' => 'テストユーザー',
            'postal_codes' => '100-0001',
            'addresses' => '東京都千代田区千代田1-1-1',
            'building_names' => 'テストマンション101号',
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

        // 2. 送付先住所変更画面で住所を登録する
        $newAddressData = [
            'postal_code' => '150-0002',
            'address' => '東京都渋谷区恵比寿2-2-2',
            'building_name' => '恵比寿タワー202号',
        ];

        $addressUpdateResponse = $this->post("/purchase/address/{$item->id}", $newAddressData);
        $addressUpdateResponse->assertStatus(302);
        $addressUpdateResponse->assertRedirect("/purchase/{$item->id}");

        // 3. 商品を購入する（Stripe決済セッション作成）
        $purchaseResponse = $this->post("/item/{$item->id}/stripe/checkout", [
            'payment_method' => 'credit_card',
            'shipping_address' => "〒{$newAddressData['postal_code']}\n{$newAddressData['address']}\n{$newAddressData['building_name']}",
        ]);

        // 期待挙動: Stripe決済画面にリダイレクトされる
        $purchaseResponse->assertStatus(302);
        $purchaseResponse->assertRedirect();

        // 期待挙動: リダイレクト先がStripe決済画面であることを確認
        $redirectUrl = $purchaseResponse->headers->get('Location');
        $this->assertStringContainsString('checkout.stripe.com', $redirectUrl);

        // 期待挙動: セッションに正しい送付先住所が保存されている
        $this->assertTrue(session()->has("shipping_address_{$item->id}"));
        $savedAddress = session("shipping_address_{$item->id}");
        $this->assertStringContainsString($newAddressData['postal_code'], $savedAddress);
        $this->assertStringContainsString($newAddressData['address'], $savedAddress);
        $this->assertStringContainsString($newAddressData['building_name'], $savedAddress);

        // 期待挙動: この時点ではOrderレコードは作成されていない（Stripe決済完了後にWebhookで作成される）
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // 期待挙動: 商品の状態を確認（Stripe決済処理により売却済みになる場合がある）
        $item->refresh();
        // 商品が売却済みになっているかどうかはStripeの処理に依存するため、ここでは確認しない
    }
}
