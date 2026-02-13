<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 評価データは作成しない
        $this->command->info("評価データは作成しません。");
    }
}
