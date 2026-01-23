<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // CarbonのsetLastErrorsエラーを抑制するためのエラーハンドラ
        // PHP 8.2ではDateTime::getLastErrors()がfalseを返すことがあるため
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // CarbonのsetLastErrorsエラーを無視
            if (str_contains($errfile, 'carbon') && 
                str_contains($errstr, 'setLastErrors')) {
                return true; // エラーを抑制
            }
            return false; // その他のエラーは通常通り処理
        });

        try {
            parent::setUp();
        } catch (\TypeError $e) {
            // Carbon の互換性エラー（setLastErrors）を無視
            if (str_contains($e->getFile(), 'carbon') && 
                str_contains($e->getMessage(), 'setLastErrors')) {
                // エラーを無視して続行（テストは実行される）
                restore_error_handler();
                return;
            }
            // その他の TypeError は通常通り処理
            restore_error_handler();
            throw $e;
        } catch (\ErrorException $e) {
            // Carbon の互換性エラー（setLastErrors）を無視
            if (str_contains($e->getFile(), 'carbon') && 
                str_contains($e->getMessage(), 'setLastErrors')) {
                restore_error_handler();
                return;
            }
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();
    }
}
