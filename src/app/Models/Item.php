<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_image_paths',
        'item_names',
        'brand_names',
        'item_prices',
        'like_counts',
        'comment_counts',
        'item_descriptions',
        'conditions',
        'is_sold',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_items');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(ItemLike::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
