@extends('layouts.app') @section('title','商品の出品')
@push('styles')
<link rel="stylesheet" href="/css/items-sell.css" />
@endpush
@section('content')
<div class="p-sell">
<form
    action="{{ url('/sell') }}"
    method="post"
    enctype="multipart/form-data"
    class="p-form"
    novalidate
>
    @csrf

    <div class="p-form__group">
        <label class="p-form__label">商品画像</label>
        <div class="p-upload">
            <div class="p-upload__drop" id="js-drop-area">
                <div class="p-upload__thumb" id="js-image-preview">画像を選択する</div>
                <input id="item_image" class="p-upload__input" type="file" name="item_image" accept="image/jpeg,image/png" />
            </div>
        </div>
        @error('item_image')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <h2 class="p-sell__section-title">商品の詳細</h2>

    <div class="p-form__group">
        <label class="p-form__label">カテゴリー</label>
        <div class="p-tags">
            @foreach($categories as $category)
            <label class="c-tag">
                <input
                    type="checkbox"
                    name="category_ids[]"
                    value="{{ $category->id }}"
                    {{ in_array($category->id, (array) old('category_ids', [])) ? 'checked' : '' }}
                />
                <span>{{ $category->category_names }}</span>
            </label>
            @endforeach
        </div>
        @error('category_ids')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <div class="p-form__group">
        <label class="p-form__label">商品の状態</label>
        <select name="condition" class="p-form__control">
            <option value="">選択してください</option>
            @foreach($conditions as $key => $label)
            <option value="{{ $key }}" {{ old('condition') == $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('condition')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <div class="p-form__group">
        <label class="p-form__label">商品名</label>
        <input
            type="text"
            name="item_name"
            value="{{ old('item_name') }}"
            class="p-form__control"
        />
        @error('item_name')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <div class="p-form__group">
        <label class="p-form__label">ブランド名</label>
        <input
            type="text"
            name="brand_name"
            value="{{ old('brand_name') }}"
            class="p-form__control"
        />
    </div>

    <div class="p-form__group">
        <label class="p-form__label">商品の説明</label>
        <textarea name="item_description" class="p-form__control" rows="4">{{
            old("item_description")
        }}</textarea>
        @error('item_description')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <div class="p-form__group">
        <label class="p-form__label">販売価格</label>
        <div class="c-price">
            <span class="c-price__prefix">￥</span>
            <input
                type="number"
                step="1"
                min="0"
                name="item_price"
                value="{{ old('item_price') }}"
                class="p-form__control c-price__input"
            />
        </div>
        @error('item_price')
        <div class="p-form__error">{{ $message }}</div>
        @enderror
    </div>

    <button class="c-button c-button--primary p-form__submit" type="submit">
        出品する
    </button>
</form>
<script>
    (function(){
        const input = document.getElementById('item_image');
        const preview = document.getElementById('js-image-preview');
        const drop = document.getElementById('js-drop-area');
        if(!input || !preview || !drop) return;
     
        drop.addEventListener('click', function(){ input.click(); });
        input.addEventListener('change', function(e){
            const file = e.target.files && e.target.files[0];
            if(!file) return;
            const reader = new FileReader();
            reader.onload = function(ev){
                preview.style.backgroundImage = 'url(' + ev.target.result + ')';
                preview.classList.add('p-upload__thumb--image');
                drop.classList.add('p-upload__drop--image');
                preview.textContent = '';
            };
            reader.readAsDataURL(file);
        });
        drop.addEventListener('dragover', function(e){ e.preventDefault(); drop.classList.add('p-upload__drop--hover'); });
        drop.addEventListener('dragleave', function(){ drop.classList.remove('p-upload__drop--hover'); });
        drop.addEventListener('drop', function(e){
            e.preventDefault();
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if(!file) return;
            const dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
            const ev = new Event('change');
            input.dispatchEvent(ev);
        });
    })();
</script>
<div>
@endsection
