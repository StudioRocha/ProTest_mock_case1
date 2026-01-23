<?php

namespace Tests\Support;

class TestValidationRules
{
    /**
     * 設定ファイルからバリデーションルールを取得（uniqueルールを除外）
     * 参照元: config/validation.php -> register.rules
     */
    public static function getRegisterRules(): array
    {
        $rules = config('validation.register.rules');
        
        // uniqueルールを除外（テスト環境ではDB依存を避けるため）
        $rules['email'] = array_filter($rules['email'], function($rule) {
            return !str_starts_with($rule, 'unique:');
        });
        
        return $rules;
    }

    /**
     * 設定ファイルからバリデーションメッセージを取得
     * 参照元: config/validation.php -> register.messages
     */
    public static function getRegisterMessages(): array
    {
        return config('validation.register.messages');
    }

    /**
     * 設定ファイルからテストデータを取得
     * 参照元: config/validation.php -> register.test_data
     */
    public static function getTestData(): array
    {
        return config('validation.register.test_data', [
            // フォールバック用のデフォルト値（設定ファイルが存在しない場合のみ使用）
            'valid_username' => 'テストユーザー',
            'valid_email' => 'test@example.com',
            'valid_password' => 'password123',
            'short_password' => '1234567', // 7文字（8文字未満）
            'different_password' => 'different_password',
        ]);
    }

    /**
     * 設定ファイルからログイン用バリデーションルールを取得
     * 参照元: config/validation.php -> login.rules
     */
    public static function getLoginRules(): array
    {
        return config('validation.login.rules');
    }

    /**
     * 設定ファイルからログイン用バリデーションメッセージを取得
     * 参照元: config/validation.php -> login.messages
     */
    public static function getLoginMessages(): array
    {
        return config('validation.login.messages');
    }

    /**
     * 設定ファイルからログイン用テストデータを取得
     * 参照元: config/validation.php -> login.test_data
     */
    public static function getLoginTestData(): array
    {
        return config('validation.login.test_data', [
            // フォールバック用のデフォルト値（設定ファイルが存在しない場合のみ使用）
            'valid_email' => 'test@example.com',
            'valid_password' => 'password123',
            'invalid_email' => 'invalid@example.com',
            'invalid_password' => 'wrongpassword',
            'invalid_format_email' => 'invalid-email-format',
        ]);
    }
}
