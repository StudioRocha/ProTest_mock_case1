<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Item;
use Tests\Support\TestItemData;

/**
 * 商品検索機能のFeatureテスト
 * 
 * テストID: 6
 */
class ItemSearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 検索欄にキーワードを入力して部分一致する商品が表示されることを検証する
     * 手順:
     *  1) 検索欄にキーワードを入力
     *  2) 検索ボタンを押す
     *
     * 期待挙動: 部分一致する商品が表示される
     */
    public function test_search_displays_partial_match_items()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);


        // 他のユーザーが出品した商品を作成（検索対象）
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);

        // ランダムな商品データを取得
        $randomItems = TestItemData::getRandomItems(2);
        $matchingItemData = $randomItems[0];
        $nonMatchingItemData = $randomItems[1];

        $matchingItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => $matchingItemData['item_names'],
            'brand_names' => $matchingItemData['brand_names'],
            'item_prices' => $matchingItemData['item_prices'],
            'item_descriptions' => $matchingItemData['item_descriptions'],
            'conditions' => $matchingItemData['conditions'],
        ]);

        // 検索に一致しない商品を作成
        $nonMatchingItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => $nonMatchingItemData['item_names'],
            'brand_names' => $nonMatchingItemData['brand_names'],
            'item_prices' => $nonMatchingItemData['item_prices'],
            'item_descriptions' => $nonMatchingItemData['item_descriptions'],
            'conditions' => $nonMatchingItemData['conditions'],
        ]);


        // 1. 検索欄にキーワードを入力（商品名全体で検索）
        $searchKeyword = $matchingItemData['item_names'];

        // 2. 検索ボタンを押す
        $response = $this->get('/?keyword=' . urlencode($searchKeyword));

        // 期待挙動: ページが正常に表示される
        $response->assertStatus(200);

        // 期待挙動: 部分一致する商品が表示される
        $response->assertSee($matchingItemData['item_names']);

        // 期待挙動: 一致しない商品は表示されない
        $response->assertDontSee($nonMatchingItemData['item_names']);
    }

 


    /**
     * 検索キーワードがマイリストページ遷移時に保持されることを検証する
     * 手順:
     *  1) ホームページで商品を検索
     *  2) 検索結果が表示される
     *  3) マイリストページに遷移
     *
     * 期待挙動: 検索キーワードが保持されている
     */
    public function test_search_keyword_is_preserved_when_navigating_to_mylist()
    {
        // テスト用データを作成
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
        ]);

        // 他のユーザーが出品した商品を作成
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);

        // ランダムな商品データを取得
        $randomItemData = TestItemData::getRandomItems(1)[0];

        // 検索対象の商品を作成
        $item = Item::factory()->create([
            'user_id' => $otherUser->id,
            'is_sold' => false,
            'item_names' => $randomItemData['item_names'],
            'brand_names' => $randomItemData['brand_names'],
            'item_prices' => $randomItemData['item_prices'],
            'item_descriptions' => $randomItemData['item_descriptions'],
            'conditions' => $randomItemData['conditions'],
        ]);

        // ログイン状態にする
        $this->actingAs($user);

        // 1. ホームページで商品を検索
        $searchKeyword = $randomItemData['item_names'];
        $searchResponse = $this->get('/?keyword=' . urlencode($searchKeyword));

        // 2. 検索結果が表示される
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($randomItemData['item_names']);

        // 3. マイリストページに遷移
        $mylistResponse = $this->get('/?tab=mylist&keyword=' . urlencode($searchKeyword));

        // 期待挙動: ページが正常に表示される
        $mylistResponse->assertStatus(200);

        // 期待挙動: 検索キーワードが保持されている（URLパラメータに含まれている）
        $mylistResponse->assertSee('value="' . $searchKeyword . '"', false);

        // 期待挙動: マイリストタブがアクティブになっている
        $mylistResponse->assertSee('p-tabs__tab--active');
    }

}
