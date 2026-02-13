<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Item;

class ExpireStripeSessions extends Command
{
    protected $signature = 'stripe:expire-sessions';
    protected $description = '期限切れのStripe決済セッションを処理し、在庫を解放する';

    public function handle()
    {
        $this->info('期限切れの決済セッションをチェック中...');
        
        // 期限切れの決済セッションを処理
        // 注意: 実際の実装では、StripeのAPIを使って期限切れのセッションを確認する必要があります
        // ここでは簡易的な実装として、過去24時間以内に作成されたが未完了の商品を対象とします
        
        $expiredItems = Item::where('is_sold', true)
            ->where('created_at', '<', now()->subHours(24))
            ->whereDoesntHave('orders') // 注文が存在しない商品
            ->get();
            
        foreach ($expiredItems as $item) {
            // 在庫を解放
            $item->update(['is_sold' => false]);
            $this->info("商品ID {$item->id} の在庫を解放しました: {$item->item_names}");
        }
        
        $this->info('期限切れ処理が完了しました。');
    }
}
