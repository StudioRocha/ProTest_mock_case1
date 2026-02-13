<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PurchaseRequest;
use App\Services\StripeService;
use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * 決済セッションを作成してStripe決済画面にリダイレクト
     */
    public function createCheckoutSession(PurchaseRequest $request, Item $item)
    {
        $user = Auth::user();
        
        // 商品が既に売却済みでないかチェック
        if ($item->is_sold) {
            return redirect()->route('items.show', $item)->with('error', 'この商品は既に売却済みです。');
        }
        
        // 自分が出品した商品は購入不可
        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $item)->with('error', '自分が出品した商品は購入できません。');
        }

        try {
            // コンビニ決済の場合は、セッション作成時点で在庫を予約
            if ($request->payment_method === 'convenience_store') {
                DB::transaction(function() use ($item, $user, $request) {
                    // 商品を売却済みにマーク
                    $item->update(['is_sold' => true]);
                    
                    // 注文を作成
                    Order::create([
                        'user_id' => $user->id,
                        'item_id' => $item->id,
                        'total_amount' => $item->item_prices,
                        'payment_method' => 'convenience_store',
                        'shipping_address' => $request->shipping_address,
                        'payment_status' => Order::PAYMENT_STATUS_PAYMENT_PENDING,
                        'trade_status' => Order::TRADE_STATUS_TRADING,
                    ]);
                });
            }
            
            $session = $this->stripeService->createCheckoutSession(
                $item,
                $user,
                $request->payment_method,
                $request->shipping_address
            );

            // Ajaxリクエストの場合はJSONで返す（コンビニ支払いの場合）
            if (($request->ajax() || $request->wantsJson()) && $request->payment_method === 'convenience_store') {
                // フラッシュメッセージをセッションに保存
                session()->flash('success', '購入手続きを受け付けました。期限内にコンビニ支払を完了してください。');
                return response()->json(['redirect_url' => $session->url]);
            }

            return redirect($session->url);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 決済成功時の処理
     */
    public function success(Request $request, Item $item)
    {
        $sessionId = $request->query('session_id');
        
        if (!$sessionId) {
            return redirect()->route('items.index')->with('error', '決済セッションが見つかりません。');
        }

        try {
            // デバッグ: セッションIDをログに出力
            Log::info('Stripe Session ID: ' . $sessionId);
            Log::info('Item ID: ' . $item->id);
            
            $session = $this->stripeService->retrieveSession($sessionId);
            
            // デバッグ: セッション情報をログに出力
            Log::info('Session payment_status: ' . $session->payment_status);
            Log::info('Session metadata: ' . json_encode($session->metadata));
            
            // コンビニ決済かどうかを判定
            $isKonbiniPayment = in_array('konbini', $session->payment_method_types);
            
            // コンビニ決済の場合は、既にcreateCheckoutSessionで処理済み
            if ($isKonbiniPayment) {
                return redirect()->route('items.index')->with('success', 'コンビニでの支払い手続きが完了しました。支払い期限までにコンビニでお支払いください。');
            }
            
            if ($session->payment_status === 'paid') {
                // 決済が完了している場合、注文を作成
                DB::transaction(function() use ($item, $session) {
                    Order::create([
                        'user_id' => $session->metadata->user_id,
                        'item_id' => $item->id,
                        'total_amount' => $item->item_prices,
                        'payment_method' => $session->payment_method_types[0],
                        'shipping_address' => $session->metadata->shipping_address,
                        'payment_status' => Order::PAYMENT_STATUS_PAID,
                        'trade_status' => Order::TRADE_STATUS_TRADING,
                    ]);
                    
                    // 商品を売却済みにマーク
                    $item->update(['is_sold' => true]);
                });

                return redirect()->route('items.index')->with('success', '購入が完了しました。');
            } else {
                return redirect()->route('items.show', $item)->with('error', '決済が完了していません。');
            }
        } catch (\Exception $e) {
            return redirect()->route('items.index')->with('error', '決済の確認に失敗しました: ' . $e->getMessage());
        }
    }

    /**
     * 決済キャンセル時の処理
     */
    public function cancel(Item $item)
    {
        return redirect()->route('items.show', $item)->with('info', '決済がキャンセルされました。');
    }
}
