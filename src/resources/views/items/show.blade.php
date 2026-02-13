@extends('layouts.app') @push('styles')
<link rel="stylesheet" href="/css/items-show.css" />
@endpush @section('title', $item->item_names) @section('content')
<div class="p-detail">
    <div class="p-detail__main">
        <div class="p-detail__image">
            <img
                class="p-detail__img"
                src="{{ asset(Str::startsWith($item->item_image_paths, 'http') ? $item->item_image_paths : (Str::startsWith($item->item_image_paths, 'images/') ? $item->item_image_paths : 'storage/'.$item->item_image_paths)) }}"
                alt="{{ $item->item_names }}"
            />
            @if($item->is_sold)
            <span class="p-detail__badge">Sold</span>
            @endif
        </div>
        <div class="p-detail__info">
            <h1 class="p-detail__name">{{ $item->item_names }}</h1>
            <div class="p-detail__brand">
                ブランド名: {{ $item->brand_names }}
            </div>
            <div class="p-detail__price">
                <span class="p-detail__price-yen">￥</span
                >{{ number_format($item->item_prices) }}（税込）
            </div>
            <div class="p-detail__meta">
                <form
                    action="{{ url('/item/'.$item->id.'/like') }}"
                    method="post"
                    class="p-like"
                >
                    @csrf
                    <button
                        type="submit"
                        class="p-like__btn {{
                            $liked ? 'p-like__btn--active' : ''
                        }}"
                    >
                        {{ $liked ? "★" : "☆" }}
                    </button>
                    <span class="p-like__count">{{ $likeCount }}</span>
                </form>
                <span class="p-detail__icon">
                    <img
                        src="{{ asset('images/ico-comment.svg') }}"
                        alt="comments"
                        class="c-icon"
                    />
                    <span
                        class="p-detail__comment-count"
                        >{{ $item->comments()->count() }}</span
                    >
                </span>
            </div>
            @php($isOwner = auth()->check() && auth()->id() === $item->user_id)
            @if($isOwner)
            <div class="p-detail__buy">
                <button
                    class="c-button c-button--primary p-detail__buy-btn p-detail__buy-btn--disabled"
                    disabled
                >
                    購入手続きへ
                </button>
                <p class="p-detail__owner-notice">
                    あなたが出品している商品です
                </p>
            </div>
            @elseif($item->is_sold)
            <div class="p-detail__buy">
                @php($hasOrder = $item->orders()->where('payment_status',
                'paid')->exists()) @if($hasOrder)
                <button
                    class="c-button c-button--primary p-detail__buy-btn p-detail__buy-btn--disabled"
                    disabled
                >
                    売却済み
                </button>
                @else
                <button
                    class="c-button c-button--primary p-detail__buy-btn p-detail__buy-btn--disabled"
                    disabled
                >
                    決済中
                </button>
                @endif
            </div>
            @else
            <div class="p-detail__buy">
                <a
                    href="{{ route('items.purchase.form', $item) }}"
                    class="c-button c-button--primary p-detail__buy-btn"
                >
                    購入手続きへ
                </a>
            </div>
            @endif
            <h2 class="p-detail__section">商品説明</h2>
            <div class="p-detail__desc">{{ $item->item_descriptions }}</div>
            <h2 class="p-detail__section">商品の情報</h2>
            <div class="p-detail__tags">
                <div>カテゴリー</div>
                @foreach($item->categories as $cat)
                <span class="c-tag"
                    ><span>{{ $cat->category_names }}</span></span
                >
                @endforeach
            </div>
            <div class="p-detail__spec">
                <div>
                    商品の状態
                    <span
                        class="p-detail__spec-value"
                        >{{ ['','良好','目立った傷や汚れ無し','やや傷や汚れあり','状態が悪い'][$item->conditions] ?? '' }}</span
                    >
                </div>
            </div>

            <div class="p-detail__comments">
                <h2 class="p-detail__section">
                    コメント({{ $item->comments()->count() }})
                </h2>
                <ul class="p-detail__comment-list">
                    @foreach($item->comments as $comment)
                    <li class="p-comment">
                        <div class="p-comment__header">
                            <div class="p-comment__avatar">
                                <div
                                    class="p-comment__avatar-bg"
                                    style="@if(optional($comment->user->profile)->avatar_paths) background-image: url('{{ asset('storage/'. $comment->user->profile->avatar_paths) }}') @endif"
                                ></div>
                            </div>
                            <div class="p-comment__user-info">
                                <div class="p-comment__user">
                                    {{ optional($comment->user->profile)->usernames ?? $comment->user->name ?? 'ユーザー' }}
                                </div>
                                <div class="p-comment__date">
                                    {{ $comment->created_at->format('Y/m/d H:i') }}
                                </div>
                            </div>
                        </div>
                        <div class="p-comment__body">
                            {{ $comment->comment_body }}
                        </div>
                    </li>
                    @endforeach
                </ul>

                <form
                    action="{{ url('/item/'.$item->id.'/comment') }}"
                    method="post"
                    class="p-form"
                >
                    @csrf
                    <div class="p-form__group">
                        <label class="p-form__label">商品へのコメント</label>
                        <textarea
                            name="comment_body"
                            class="p-form__control"
                            rows="5"
                            >{{ old("comment_body") }}</textarea
                        >
                        @error('comment_body')
                        <div class="p-form__error">{{ $message }}</div>
                        @enderror
                    </div>
                    <button
                        class="c-button c-button--primary p-form__submit"
                        type="submit"
                    >
                        コメントを送信する
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
