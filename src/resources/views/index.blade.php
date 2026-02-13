@extends('layouts.app') @section('title', '商品一覧') @push('styles')
<link rel="stylesheet" href="/css/items-index.css" />
@endpush @section('content')
<nav class="p-top-tabs">
    <ul class="p-tabs">
        <li>
            <a
                href="{{ route('items.index', array_filter(['keyword' => $keyword])) }}"
                class="p-tabs__tab {{
                    $activeTab === 'recommend' ? 'p-tabs__tab--active' : ''
                }}"
                >おすすめ</a
            >
        </li>
        <li>
            <a
                href="{{ route('items.index', array_filter(['tab' => 'mylist','keyword' => $keyword])) }}"
                class="p-tabs__tab {{
                    $activeTab === 'mylist' ? 'p-tabs__tab--active' : ''
                }}"
                >マイリスト</a
            >
        </li>
    </ul>
</nav>

<div class="p-top">
    <ul class="p-grid">
        @forelse($items as $item)
        <li>
            <a href="/item/{{ $item->id }}" class="c-card">
                <div class="c-card__thumb">
                    @if($item->is_sold)
                    <span class="c-card__badge">Sold</span>
                    @endif @if($item->item_image_paths)
                    <img
                        src="{{ asset(Str::startsWith($item->item_image_paths, 'http') ? $item->item_image_paths : (Str::startsWith($item->item_image_paths, 'images/') ? $item->item_image_paths : 'storage/'.$item->item_image_paths)) }}"
                        alt="{{ $item->item_names }}"
                        class="c-card__img"
                    />
                    @else
                    <div class="c-card__img c-card__img--placeholder">商品画像</div>
                    @endif
                </div>
                <div class="c-card__name">{{ $item->item_names }}</div>
            </a>
        </li>
        @empty
        <li class="c-empty">商品がありません。</li>
        @endforelse
    </ul>

    <div class="p-pagination">
        {{ $items->links() }}
    </div>
</div>
@endsection
