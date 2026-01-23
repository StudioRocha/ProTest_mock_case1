<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'item_image_paths' => 'images/items/watch.jpg',
            'item_names' => $this->faker->words(3, true),
            'brand_names' => $this->faker->company(),
            'item_prices' => $this->faker->numberBetween(100, 50000),
            'like_counts' => $this->faker->numberBetween(0, 50),
            'comment_counts' => $this->faker->numberBetween(0, 10),
            'item_descriptions' => substr($this->faker->paragraph(), 0, 255),
            'conditions' => $this->faker->numberBetween(1, 4),
            'is_sold' => false,
        ];
    }

    public function sold()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sold' => true,
            ];
        });
    }

    public function available()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_sold' => false,
            ];
        });
    }
}
