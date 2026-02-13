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
 * 評価平均確認機能のFeatureテスト
 *
 * テストID: 18
 * US002: ユーザーは自分の取引評価の平均を確認することができる
 * FN005: 評価平均確認機能
 * 
 * 注意: 仕様書では「プロフィール画面」と記載されているが、
 * 実際の実装では「マイページ画面（/mypage）」に評価平均が表示される。
 * プロフィール編集画面（/mypage/profile）には評価は表示されない。
 */
class TestID18_RatingAverageFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 評価がある場合、平均評価がマイページに表示されることを検証する
     * FN005: 評価平均確認機能
     */
    public function test_average_rating_is_displayed_when_ratings_exist()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => $now,
        ]);

        $rater1 = User::factory()->create([
            'email' => 'rater1@example.com',
            'email_verified_at' => $now,
        ]);

        $rater2 = User::factory()->create([
            'email' => 'rater2@example.com',
            'email_verified_at' => $now,
        ]);

        $category = Category::create(['category_names' => 'ファッション']);
        $itemData = TestItemData::getRandomItems(1)[0];

        $item = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => true,
            'item_names' => $itemData['item_names'],
            'brand_names' => $itemData['brand_names'],
            'item_prices' => $itemData['item_prices'],
            'item_descriptions' => $itemData['item_descriptions'],
            'conditions' => $itemData['conditions'],
        ]);
        $item->categories()->attach($category->id);

        $order1 = Order::create([
            'user_id' => $rater1->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
            'shipping_address' => 'テスト住所',
        ]);

        $order2 = Order::create([
            'user_id' => $rater2->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
            'shipping_address' => 'テスト住所',
        ]);

        // 評価を作成（3点と5点）
        Rating::create([
            'order_id' => $order1->id,
            'rater_id' => $rater1->id,
            'rated_id' => $user->id,
            'rating' => 3,
        ]);

        Rating::create([
            'order_id' => $order2->id,
            'rater_id' => $rater2->id,
            'rated_id' => $user->id,
            'rating' => 5,
        ]);

        $this->actingAs($user);
        $response = $this->get('/mypage');

        $response->assertStatus(200);
        // 平均評価（(3+5)/2 = 4）が表示されることを確認（星マークが4つ表示される）
        $response->assertSee('★', false);
    }

    /**
     * 評価がない場合、評価が表示されないことを検証する
     * FN005: 評価平均確認機能
     */
    public function test_rating_not_displayed_when_no_ratings_exist()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => $now,
        ]);

        $this->actingAs($user);
        $response = $this->get('/mypage');

        $response->assertStatus(200);
        // 評価がない場合、星マークが表示されないことを確認
        // ユーザー名は表示されるが、評価セクションは表示されない
        $content = $response->getContent();
        $this->assertStringNotContainsString('p-mypage__rating', $content);
    }

    /**
     * 平均評価が小数の場合、四捨五入されることを検証する
     * FN005: 評価平均確認機能
     */
    public function test_average_rating_is_rounded_when_decimal()
    {
        $now = date('Y-m-d H:i:s');
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => $now,
        ]);

        $rater1 = User::factory()->create([
            'email' => 'rater1@example.com',
            'email_verified_at' => $now,
        ]);

        $rater2 = User::factory()->create([
            'email' => 'rater2@example.com',
            'email_verified_at' => $now,
        ]);

        $rater3 = User::factory()->create([
            'email' => 'rater3@example.com',
            'email_verified_at' => $now,
        ]);

        $category = Category::create(['category_names' => 'ファッション']);
        $itemData = TestItemData::getRandomItems(1)[0];

        $item = Item::factory()->create([
            'user_id' => $user->id,
            'is_sold' => true,
            'item_names' => $itemData['item_names'],
            'brand_names' => $itemData['brand_names'],
            'item_prices' => $itemData['item_prices'],
            'item_descriptions' => $itemData['item_descriptions'],
            'conditions' => $itemData['conditions'],
        ]);
        $item->categories()->attach($category->id);

        $order1 = Order::create([
            'user_id' => $rater1->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
            'shipping_address' => 'テスト住所',
        ]);

        $order2 = Order::create([
            'user_id' => $rater2->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
            'shipping_address' => 'テスト住所',
        ]);

        $order3 = Order::create([
            'user_id' => $rater3->id,
            'item_id' => $item->id,
            'total_amount' => $item->item_prices,
            'payment_method' => 'card',
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'trade_status' => Order::TRADE_STATUS_COMPLETED,
            'shipping_address' => 'テスト住所',
        ]);

        // 評価を作成（3点、4点、4点 → 平均3.67 → 四捨五入で4）
        Rating::create([
            'order_id' => $order1->id,
            'rater_id' => $rater1->id,
            'rated_id' => $user->id,
            'rating' => 3,
        ]);

        Rating::create([
            'order_id' => $order2->id,
            'rater_id' => $rater2->id,
            'rated_id' => $user->id,
            'rating' => 4,
        ]);

        Rating::create([
            'order_id' => $order3->id,
            'rater_id' => $rater3->id,
            'rated_id' => $user->id,
            'rating' => 4,
        ]);

        $this->actingAs($user);
        $response = $this->get('/mypage');

        $response->assertStatus(200);
        // 平均評価（(3+4+4)/3 = 3.67 → 四捨五入で4）が表示されることを確認
        // 星マークが4つ表示される（filledクラスが4つ）
        $content = $response->getContent();
        preg_match_all('/p-mypage__star--filled/', $content, $matches);
        $this->assertEquals(4, count($matches[0]), '平均評価3.67が四捨五入されて4として表示されるべき');
    }
}
