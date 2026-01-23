<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // 支払い状態
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_PAYMENT_PENDING = 'payment_pending';

    // 取引状態
    public const TRADE_STATUS_TRADING = 'trading';
    public const TRADE_STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id', 'item_id', 'total_amount', 'payment_status', 'trade_status', 'payment_method', 'shipping_address', 'comment',
        'buyer_last_viewed_at', 'seller_last_viewed_at', 'chat_access_token', 'chat_access_token_expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function rating()
    {
        return $this->hasOne(Rating::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }
}


