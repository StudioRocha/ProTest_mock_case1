<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\AddressChangeController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\EmailAuthController;
use App\Http\Controllers\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 未ログイン時はauthミドルウェアで/loginへリダイレクト
Route::get('/', [ItemController::class, 'index'])->name('items.index');

// メール認証関連ルート（認証前でもアクセス可能）
Route::get('/email/guide', [EmailAuthController::class, 'guide'])->name('email.guide');
Route::get('/email/verify/{token}', [EmailAuthController::class, 'verify'])->name('email.verify');
Route::get('/email/resend', [EmailAuthController::class, 'resend'])->name('email.resend');
Route::get('/email/auto-verify', [EmailAuthController::class, 'autoVerify'])->name('email.auto-verify');

// メール内のトークンからチャット画面にアクセス（認証前でもアクセス可能）
Route::get('/chat/{item}/access/{token}', [\App\Http\Controllers\ChatController::class, 'access'])->name('chat.access');
Route::post('/chat/{item}/switch/{token}', [\App\Http\Controllers\ChatController::class, 'switchAccount'])->name('chat.switch-account');

Route::middleware(['auth', 'block.unverified'])->group(function () {
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/sell', [\App\Http\Controllers\ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [\App\Http\Controllers\ItemController::class, 'store'])->name('items.store');
    Route::get('/purchase/{item}', [\App\Http\Controllers\ItemController::class, 'purchaseForm'])->name('items.purchase.form');
    Route::post('/item/{item}/purchase', [\App\Http\Controllers\ItemController::class, 'purchase'])->name('items.purchase');
    Route::get('/purchase/address/{item}', [AddressChangeController::class, 'show'])->name('address.change');
    Route::post('/purchase/address/{item}', [AddressChangeController::class, 'update'])->name('address.update');
    Route::post('/item/{item}/stripe/checkout', [StripeController::class, 'createCheckoutSession'])->name('stripe.checkout');
    Route::get('/stripe/success/{item}', [StripeController::class, 'success'])->name('stripe.success');
    Route::get('/stripe/cancel/{item}', [StripeController::class, 'cancel'])->name('stripe.cancel');
    Route::post('/item/{item}/comment', [\App\Http\Controllers\ItemController::class, 'comment'])->name('items.comment');
    Route::post('/item/{item}/like', [\App\Http\Controllers\ItemController::class, 'like'])->name('items.like');
    Route::get('/item/{item}/payment-status', [\App\Http\Controllers\ItemController::class, 'paymentStatus'])->name('items.payment.status');
    
    // 取引チャット
    Route::get('/chat/{item}', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/{item}', [\App\Http\Controllers\ChatController::class, 'store'])->name('chat.store');
    Route::put('/chat/{item}/message/{message}', [\App\Http\Controllers\ChatController::class, 'update'])->name('chat.update');
    Route::delete('/chat/{item}/message/{message}', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('chat.destroy');
    Route::post('/chat/{item}/complete', [\App\Http\Controllers\ChatController::class, 'complete'])->name('chat.complete');
    Route::post('/chat/{item}/seller-rate', [\App\Http\Controllers\ChatController::class, 'sellerRate'])->name('chat.seller-rate');
    Route::post('/chat/{item}/save-draft', [\App\Http\Controllers\ChatController::class, 'saveDraft'])->name('chat.save-draft');
    Route::post('/chat/{item}/mark-as-read', [\App\Http\Controllers\ChatController::class, 'markAsRead'])->name('chat.mark-as-read');
});

// 商品詳細（未ログインでも閲覧可）
Route::get('/item/{item}', [\App\Http\Controllers\ItemController::class, 'show'])->name('items.show');

// Auth routes (Fortify互換). GETは自作ビュー、POSTは統合されたコントローラへ委譲
Route::get('/login', [LoginController::class, 'show'])->name('login')->middleware(['guest', 'block.unverified']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::get('/register', [RegisterController::class, 'show'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'store']);