<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'usernames' => $this->faker->name(),
            'postal_codes' => $this->faker->regexify('[0-9]{3}-[0-9]{4}'), // 123-4567形式
            'addresses' => $this->faker->prefecture() . $this->faker->city() . $this->faker->streetAddress(), // 都道府県+市区町村+住所
            'building_names' => $this->faker->optional(0.7)->buildingNumber(), // 70%の確率で建物名あり
        ];
    }
}
