<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // 3人のダミーユーザーを作成
        $usersData = [
            [
                'name' => '田中 太郎',
                'email' => 'tanaka@example.com',
                'usernames' => '田中 太郎',
                'postal_codes' => '100-0001',
                'addresses' => '東京都千代田区千代田1-1',
                'building_names' => 'テストマンション 101',
            ],
            [
                'name' => '佐藤 花子',
                'email' => 'sato@example.com',
                'usernames' => '佐藤 花子',
                'postal_codes' => '150-0001',
                'addresses' => '東京都渋谷区神宮前1-1',
                'building_names' => 'テストアパート 201',
            ],
            [
                'name' => '鈴木 一郎',
                'email' => 'suzuki@example.com',
                'usernames' => '鈴木 一郎',
                'postal_codes' => '530-0001',
                'addresses' => '大阪府大阪市北区梅田1-1',
                'building_names' => 'テストビル 301',
            ],
        ];

        foreach ($usersData as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // プロフィールを作成
            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'usernames' => $userData['usernames'],
                    'postal_codes' => $userData['postal_codes'],
                    'addresses' => $userData['addresses'],
                    'building_names' => $userData['building_names'],
                    'avatar_paths' => null,
                ]
            );

            $this->command->info("ユーザー「{$userData['name']}」を作成しました。");
        }

        $this->command->info('ユーザーダミーデータ3人を作成しました。');
    }
}
