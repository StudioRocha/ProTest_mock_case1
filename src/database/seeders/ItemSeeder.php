<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\User;
use App\Models\Category;

class ItemSeeder extends Seeder
{
    public function run()
    {
        // ユーザーを取得（UserSeederで作成された3人）
        $users = User::all();
        if ($users->count() < 3) {
            $this->command->error('ユーザーが3人未満です。先にUserSeederを実行してください。');
            return;
        }

        // ユーザー1: CO01～CO05を出品
        $user1 = $users->get(0);
        // ユーザー2: CO06～CO10を出品
        $user2 = $users->get(1);
        // ユーザー3: 商品なし

        // カテゴリーを取得
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->error('カテゴリーが存在しません。先にCategorySeederを実行してください。');
            return;
        }

        // 商品データ一覧の10個の商品を固定で作成
        $predefinedItemsData = [
            [
                'item_code' => 'CO01',
                'item_names' => '腕時計',
                'item_prices' => 15000,
                'brand_names' => 'Rolax',
                'item_descriptions' => 'スタイリッシュなデザインのメンズ腕時計',
                'item_image_paths' => 'images/items/watch.jpg',
                'conditions' => 1, // 良好
            ],
            [
                'item_code' => 'CO02',
                'item_names' => 'HDD',
                'item_prices' => 5000,
                'brand_names' => '西芝',
                'item_descriptions' => '高速で信頼性の高いハードディスク',
                'item_image_paths' => 'images/items/hdd.jpg',
                'conditions' => 2, // 目立った傷や汚れなし
            ],
            [
                'item_code' => 'CO03',
                'item_names' => '玉ねぎ3束',
                'item_prices' => 300,
                'brand_names' => 'なし',
                'item_descriptions' => '新鮮な玉ねぎ3束のセット',
                'item_image_paths' => 'images/items/onion.jpg',
                'conditions' => 3, // やや傷や汚れあり
            ],
            [
                'item_code' => 'CO04',
                'item_names' => '革靴',
                'item_prices' => 4000,
                'brand_names' => '',
                'item_descriptions' => 'クラシックなデザインの革靴',
                'item_image_paths' => 'images/items/shoes.jpg',
                'conditions' => 4, // 状態が悪い
            ],
            [
                'item_code' => 'CO05',
                'item_names' => 'ノートPC',
                'item_prices' => 45000,
                'brand_names' => '',
                'item_descriptions' => '高性能なノートパソコン',
                'item_image_paths' => 'images/items/laptop.jpg',
                'conditions' => 1, // 良好
            ],
            [
                'item_code' => 'CO06',
                'item_names' => 'マイク',
                'item_prices' => 8000,
                'brand_names' => 'なし',
                'item_descriptions' => '高音質のレコーディング用マイク',
                'item_image_paths' => 'images/items/mic.jpg',
                'conditions' => 2, // 目立った傷や汚れなし
            ],
            [
                'item_code' => 'CO07',
                'item_names' => 'ショルダーバッグ',
                'item_prices' => 3500,
                'brand_names' => '',
                'item_descriptions' => 'おしゃれなショルダーバッグ',
                'item_image_paths' => 'images/items/bag.jpg',
                'conditions' => 3, // やや傷や汚れあり
            ],
            [
                'item_code' => 'CO08',
                'item_names' => 'タンブラー',
                'item_prices' => 500,
                'brand_names' => 'なし',
                'item_descriptions' => '使いやすいタンブラー',
                'item_image_paths' => 'images/items/tumbler.jpg',
                'conditions' => 4, // 状態が悪い
            ],
            [
                'item_code' => 'CO09',
                'item_names' => 'コーヒーミル',
                'item_prices' => 4000,
                'brand_names' => 'Starbacks',
                'item_descriptions' => '手動のコーヒーミル',
                'item_image_paths' => 'images/items/coffee-grinder.jpg',
                'conditions' => 1, // 良好
            ],
            [
                'item_code' => 'CO10',
                'item_names' => 'メイクセット',
                'item_prices' => 2500,
                'brand_names' => '',
                'item_descriptions' => '便利なメイクアップセット',
                'item_image_paths' => 'images/items/makeup-set.jpg',
                'conditions' => 2, // 目立った傷や汚れなし
            ],
        ];

        // 商品コードとユーザーのマッピング
        // CO01～CO05: ユーザー1
        // CO06～CO10: ユーザー2
        foreach ($predefinedItemsData as $itemData) {
            $itemCode = $itemData['item_code'];
            
            // 商品コードに応じてユーザーを割り当て
            $codeNumber = (int)substr($itemCode, 2);
            if ($codeNumber >= 1 && $codeNumber <= 5) {
                $assignedUser = $user1;
            } elseif ($codeNumber >= 6 && $codeNumber <= 10) {
                $assignedUser = $user2;
            } else {
                $this->command->error("商品コード「{$itemCode}」のユーザー割り当てができません。");
                continue;
            }
            
            $item = Item::firstOrCreate(
                ['item_names' => $itemData['item_names']], // 商品名で重複チェック
                [
                    'user_id' => $assignedUser->id,
                    'item_image_paths' => $itemData['item_image_paths'], // ローカルパスをそのまま使用
                    'item_names' => $itemData['item_names'],
                    'brand_names' => $itemData['brand_names'] ?? null,
                    'item_prices' => $itemData['item_prices'],
                    'like_counts' => 0,
                    'comment_counts' => rand(0, 10),
                    'item_descriptions' => $itemData['item_descriptions'],
                    'conditions' => $itemData['conditions'],
                    'is_sold' => false, // 常に購入可能な状態
                ]
            );
            
            // ランダムに1-3個のカテゴリーを割り当て
            $randomCategories = $categories->random(rand(1, 3));
            $item->categories()->sync($randomCategories->pluck('id'));
            
            $this->command->info("商品「{$itemData['item_names']}」をユーザー「{$assignedUser->name}」が出品しました。");
        }

        $this->command->info('商品ダミーデータを作成しました。');
        $this->command->info('固定商品10個を作成しました（CO01～CO05: ユーザー1、CO06～CO10: ユーザー2）。');
    }

}
