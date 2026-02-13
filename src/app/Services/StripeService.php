<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Str;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
        
        // 環境に応じてStripeの設定を調整
        if (config('app.env') === 'production') {
            // 本番環境の設定
            Stripe::setApiVersion('2020-08-27');
        }
    }

    /**
     * 決済セッションを作成
     */
    public function createCheckoutSession($item, $user, $paymentMethod, $shippingAddress)
    {
        try {
            $sessionData = [
                'payment_method_types' => [$paymentMethod === 'convenience_store' ? 'konbini' : 'card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $item->item_names,
                            'images' => [asset(Str::startsWith($item->item_image_paths,'http') ? $item->item_image_paths : 'storage/'.$item->item_image_paths)],
                        ],
                        'unit_amount' => $item->item_prices,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.success', ['item' => $item->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('items.show', $item),
                'metadata' => [
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'shipping_address' => $shippingAddress,
                    'payment_method' => $paymentMethod,
                ],
            ];

            // コンビニ決済の場合は顧客情報を追加
            if ($paymentMethod === 'convenience_store') {
                $sessionData['customer_email'] = $user->email;
                // コンビニ決済の場合、Stripe Checkoutの完了後に自動的にリダイレクト
                $sessionData['submit_type'] = 'auto';
            }

            $session = Session::create($sessionData);

            return $session;
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripe決済セッションの作成に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * セッションIDからセッション情報を取得
     */
    public function retrieveSession($sessionId)
    {
        try {
            return Session::retrieve($sessionId);
        } catch (ApiErrorException $e) {
            throw new \Exception('Stripeセッションの取得に失敗しました: ' . $e->getMessage());
        }
    }
}
