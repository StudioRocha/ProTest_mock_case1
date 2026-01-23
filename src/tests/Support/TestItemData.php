<?php

namespace Tests\Support;

/**
 * テスト用商品データヘルパークラス
 * ItemSeederの商品データからランダムに選択してテストに使用
 */
class TestItemData
{
    /**
     * ItemSeederで定義されている商品データ一覧
     */
    private static $predefinedItems = [
        [
            'item_names' => '腕時計',
            'item_prices' => 15000,
            'brand_names' => 'Rolax',
            'item_descriptions' => 'スタイリッシュなデザインのメンズ腕時計',
                'item_image_paths' => 'images/items/watch.jpg',
            'conditions' => 1, // 良好
        ],
        [
            'item_names' => 'HDD',
            'item_prices' => 5000,
            'brand_names' => '西芝',
            'item_descriptions' => '高速で信頼性の高いハードディスク',
                'item_image_paths' => 'images/items/hdd.jpg',
            'conditions' => 2, // 目立った傷や汚れなし
        ],
        [
            'item_names' => '玉ねぎ3束',
            'item_prices' => 300,
            'brand_names' => 'なし',
            'item_descriptions' => '新鮮な玉ねぎ3束のセット',
                'item_image_paths' => 'images/items/onion.jpg',
            'conditions' => 3, // やや傷や汚れあり
        ],
        [
            'item_names' => '革靴',
            'item_prices' => 4000,
            'brand_names' => '',
            'item_descriptions' => 'クラシックなデザインの革靴',
                'item_image_paths' => 'images/items/shoes.jpg',
            'conditions' => 4, // 状態が悪い
        ],
        [
            'item_names' => 'ノートPC',
            'item_prices' => 45000,
            'brand_names' => '',
            'item_descriptions' => '高性能なノートパソコン',
                'item_image_paths' => 'images/items/laptop.jpg',
            'conditions' => 1, // 良好
        ],
        [
            'item_names' => 'マイク',
            'item_prices' => 8000,
            'brand_names' => 'なし',
            'item_descriptions' => '高音質のレコーディング用マイク',
                'item_image_paths' => 'images/items/mic.jpg',
            'conditions' => 2, // 目立った傷や汚れなし
        ],
        [
            'item_names' => 'ショルダーバッグ',
            'item_prices' => 3500,
            'brand_names' => '',
            'item_descriptions' => 'おしゃれなショルダーバッグ',
                'item_image_paths' => 'images/items/bag.jpg',
            'conditions' => 3, // やや傷や汚れあり
        ],
        [
            'item_names' => 'タンブラー',
            'item_prices' => 500,
            'brand_names' => 'なし',
            'item_descriptions' => '使いやすいタンブラー',
                'item_image_paths' => 'images/items/tumbler.jpg',
            'conditions' => 4, // 状態が悪い
        ],
        [
            'item_names' => 'コーヒーミル',
            'item_prices' => 4000,
            'brand_names' => 'Starbacks',
            'item_descriptions' => '手動のコーヒーミル',
                'item_image_paths' => 'images/items/coffee-grinder.jpg',
            'conditions' => 1, // 良好
        ],
        [
            'item_names' => 'メイクセット',
            'item_prices' => 2500,
            'brand_names' => '',
            'item_descriptions' => '便利なメイクアップセット',
                'item_image_paths' => 'images/items/makeup-set.jpg',
            'conditions' => 2, // 目立った傷や汚れなし
        ],
    ];

    /**
     * ランダムな商品データを取得
     * 
     * @param int $count 取得する商品数
     * @return array 商品データの配列
     */
    public static function getRandomItems(int $count = 1): array
    {
        $items = self::$predefinedItems;
        shuffle($items);
        return array_slice($items, 0, $count);
    }

    /**
     * 特定の商品名を含む商品データを取得
     * 
     * @param string $keyword 検索キーワード
     * @return array|null マッチした商品データ、見つからない場合はnull
     */
    public static function getItemByName(string $keyword): ?array
    {
        foreach (self::$predefinedItems as $item) {
            if (str_contains($item['item_names'], $keyword)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 特定のブランド名を含む商品データを取得
     * 
     * @param string $brand ブランド名
     * @return array|null マッチした商品データ、見つからない場合はnull
     */
    public static function getItemByBrand(string $brand): ?array
    {
        foreach (self::$predefinedItems as $item) {
            if (str_contains($item['brand_names'], $brand)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 全ての商品データを取得
     * 
     * @return array 全商品データの配列
     */
    public static function getAllItems(): array
    {
        return self::$predefinedItems;
    }

    /**
     * 商品名のリストを取得
     * 
     * @return array 商品名の配列
     */
    public static function getItemNames(): array
    {
        return array_column(self::$predefinedItems, 'item_names');
    }

    /**
     * ブランド名のリストを取得（重複除去）
     * 
     * @return array ブランド名の配列
     */
    public static function getBrandNames(): array
    {
        $brands = array_column(self::$predefinedItems, 'brand_names');
        return array_unique(array_filter($brands));
    }
}
