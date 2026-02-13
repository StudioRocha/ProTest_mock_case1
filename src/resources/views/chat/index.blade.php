@extends('layouts.app')
@section('title', '取引チャット')
@push('styles')
<link rel="stylesheet" href="/css/chat.css" />
@endpush

@php
use Illuminate\Support\Str;
@endphp

@section('content')
<div class="p-chat"
     data-is-buyer="{{ $isBuyer ? '1' : '0' }}"
     data-save-draft-url="{{ route('chat.save-draft', $item) }}"
     data-csrf-token="{{ csrf_token() }}"
     data-update-url-template="{{ route('chat.update', ['item' => $item->id, 'message' => ':messageId']) }}"
     data-destroy-url-template="{{ route('chat.destroy', ['item' => $item->id, 'message' => ':messageId']) }}"
     data-mark-as-read-url="{{ route('chat.mark-as-read', $item) }}">
    <div class="p-chat__container">
        <!-- サイドバー -->
        @include('components.chat.sidebar', ['otherTradingOrders' => $otherTradingOrders])

        <!-- メインコンテンツ -->
        <div class="p-chat__main">
            <!-- 取引ヘッダー -->
            <header class="p-chat__header">
                <div class="p-chat__header-user">
                    <div class="p-chat__avatar">
                        <div class="p-chat__avatar-bg" style="@if(optional($otherUser->profile)->avatar_paths) background-image:url('{{ asset('storage/'. $otherUser->profile->avatar_paths) }}') @endif"></div>
                    </div>
                    <h1 class="p-chat__header-title">「{{ optional($otherUser->profile)->usernames ?? $otherUser->name }}」さんとの取引画面</h1>
                </div>
                @if($isBuyer && (!isset($buyerHasRated) || !$buyerHasRated))
                <button class="p-chat__complete-btn" id="completeTransactionBtn">
                    取引を完了する
                </button>
                @endif
            </header>

            <!-- 商品情報 -->
            <div class="p-chat__product">
                <div class="p-chat__product-image">
                    @if($item->item_image_paths)
                    <img
                        src="{{ asset(Str::startsWith($item->item_image_paths, 'http') ? $item->item_image_paths : (Str::startsWith($item->item_image_paths, 'images/') ? $item->item_image_paths : 'storage/'.$item->item_image_paths)) }}"
                        alt="{{ $item->item_names }}"
                        class="p-chat__product-img"
                    />
                    @else
                    <div class="p-chat__product-placeholder">商品画像</div>
                    @endif
                </div>
                <div class="p-chat__product-info">
                    <div class="p-chat__product-name">{{ $item->item_names }}</div>
                    <div class="p-chat__product-price">¥{{ number_format($item->item_prices) }}</div>
                </div>
            </div>

            <!-- メッセージ表示エリア -->
            <div class="p-chat__messages" id="chatMessages">
                @forelse($messages as $message)
                <div class="p-chat__message {{ $message->user_id === Auth::id() ? 'p-chat__message--own' : '' }}"
                     data-message-id="{{ $message->id }}"
                     @if($lastReadMessageId && $message->id == $lastReadMessageId) data-last-read="true" @endif>
                    <div class="p-chat__message-content">
                        <div class="p-chat__message-header">
                            <div class="p-chat__message-name">{{ optional($message->user->profile)->usernames ?? $message->user->name }}</div>
                            <div class="p-chat__message-avatar">
                                <div class="p-chat__message-avatar-bg" style="@if(optional($message->user->profile)->avatar_paths) background-image:url('{{ asset('storage/'. $message->user->profile->avatar_paths) }}') @endif"></div>
                            </div>
                        </div>
                        @if($message->message)
                        <div class="p-chat__message-bubble">
                            {{ $message->message }}
                        </div>
                        @endif
                        @if($message->image_path)
                        <div class="p-chat__message-bubble p-chat__message-bubble--image">
                            <div class="p-chat__message-image">
                                <img src="{{ asset('storage/'. $message->image_path) }}" alt="メッセージ画像" class="p-chat__message-img" />
                            </div>
                        </div>
                        @endif
                        @if($message->user_id === Auth::id())
                        <div class="p-chat__message-actions">
                            <button class="p-chat__message-edit" data-message-id="{{ $message->id }}">編集</button>
                            <button class="p-chat__message-delete" data-message-id="{{ $message->id }}">削除</button>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="p-chat__messages-empty">まだメッセージがありません</div>
                @endforelse
            </div>

            <!-- メッセージ入力フォーム -->
            <div class="p-chat__input-area">
                @if($errors->any())
                <div class="p-chat__errors">
                    @foreach($errors->all() as $error)
                    <div class="p-chat__error">{{ $error }}</div>
                    @endforeach
                </div>
                @endif
                <form action="{{ route('chat.store', $item) }}" method="POST" class="p-chat__form" id="chatForm" enctype="multipart/form-data">
                    @csrf
                    <input
                        type="text"
                        name="message"
                        value="{{ old('message', $savedMessage) }}"
                        placeholder="取引メッセージを記入してください"
                        class="p-chat__input"
                    />
                    <input
                        type="file"
                        name="image"
                        id="imageInput"
                        accept="image/*"
                        style="display: none;"
                    />
                    <button type="button" class="p-chat__image-btn" id="imageBtn">画像を追加</button>
                    <div id="imagePreview" class="p-chat__image-preview" style="display: none;">
                        <img id="previewImg" src="" alt="プレビュー" class="p-chat__preview-img" />
                        <button type="button" id="removeImageBtn" class="p-chat__remove-image-btn">×</button>
                    </div>
                    <button type="submit" class="p-chat__send-btn">
                        <img src="{{ asset('images/sendmessage.jpg') }}" alt="送信" class="p-chat__send-img" />
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 取引完了モーダル（購入者のみ） -->
@include('components.chat.complete-modal', ['isBuyer' => $isBuyer, 'item' => $item])

<!-- 出品者評価モーダル（購入者が評価済みの場合のみ） -->
@if(isset($canSellerRate) && $canSellerRate)
<div class="p-chat__modal" id="sellerRateModal" style="display: none;">
    <div class="p-chat__modal-content p-chat__modal-content--complete">
        <h2 class="p-chat__modal-title">取引が完了しました。</h2>
        <form action="{{ route('chat.seller-rate', $item) }}" method="POST" id="sellerRateForm">
            @csrf
            <div class="p-chat__modal-rating">
                <p class="p-chat__modal-rating-question">今回の取引相手はどうでしたか？</p>
                <div class="p-chat__modal-stars">
                    <input type="radio" name="rating" id="sellerRating1" value="1" required>
                    <label for="sellerRating1" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="sellerRating2" value="2" required>
                    <label for="sellerRating2" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="sellerRating3" value="3" required>
                    <label for="sellerRating3" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="sellerRating4" value="4" required>
                    <label for="sellerRating4" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                    <input type="radio" name="rating" id="sellerRating5" value="5" required>
                    <label for="sellerRating5" class="p-chat__star-label">
                        <span class="p-chat__star">★</span>
                    </label>
                </div>
                @if($errors->any())
                <div class="p-chat__modal-errors">
                    @foreach($errors->all() as $error)
                    <div class="p-chat__modal-error">{{ $error }}</div>
                    @endforeach
                </div>
                @endif
            </div>
            <div class="p-chat__modal-actions">
                <button type="submit" class="p-chat__modal-submit">送信する</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- メッセージ編集モーダル -->
<div class="p-chat__modal" id="editModal" style="display: none;">
    <div class="p-chat__modal-content p-chat__modal-content--edit">
        <h2 class="p-chat__modal-title">メッセージを編集</h2>
        <form id="editMessageForm" method="POST">
            @csrf
            @method('PUT')
            @if($errors->any())
            <div class="p-chat__modal-errors">
                @foreach($errors->all() as $error)
                <div class="p-chat__modal-error">{{ $error }}</div>
                @endforeach
            </div>
            @endif
            <textarea
                name="message"
                class="p-chat__modal-textarea @error('message') p-chat__modal-textarea--error @enderror"
            >{{ old('message') }}</textarea>
            <div class="p-chat__modal-actions">
                <button type="button" class="p-chat__modal-cancel" id="cancelEditBtn">キャンセル</button>
                <button type="submit" class="p-chat__modal-submit">更新</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/chat.js') }}"></script>
@endpush
@endsection
