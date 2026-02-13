@extends('layouts.app') @section('title', 'プロフィール設定') @push('styles')
<link rel="stylesheet" href="/css/profile.css" />
@endpush @section('content')
<div class="p-auth">
    <h1 class="p-auth__title">プロフィール設定</h1>
    <form
        action="{{ url('/mypage/profile') }}"
        method="post"
        enctype="multipart/form-data"
        class="p-form"
        novalidate
    >
        @csrf @method('put')
        <div class="p-form__group">
            <label class="p-form__label"></label>
            <div class="p-avatar">
                <div class="p-avatar__thumb">
                    <div
                        id="js-avatar-preview"
                        class="p-avatar__bg"
                        style="@if(optional($profile)->avatar_paths) background-image: url('{{ asset('storage/'. $profile->avatar_paths) }}') @endif"
                    ></div>
                </div>
                <div class="p-avatar__pick">
                    <input
                        id="avatar"
                        class="p-avatar__input"
                        type="file"
                        name="avatar"
                        accept="image/jpeg,image/png"
                    />
                    <label
                        for="avatar"
                        class="c-button c-button--outline-danger"
                        >画像を選択する</label
                    >
                </div>
            </div>
            <div class="p-form__hint"></div>
            @error('avatar')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="username">ユーザー名</label>
            <input
                class="p-form__control"
                type="text"
                id="username"
                name="username"
                value="{{ old('username', optional($profile)->usernames) }}"
                placeholder="例）山田太郎（20文字以内）"
            />
            @error('username')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="postal_code">郵便番号</label>
            <input
                class="p-form__control"
                type="text"
                id="postal_code"
                name="postal_code"
                value="{{ old('postal_code', optional($profile)->postal_codes) }}"
                placeholder="123-4567"
            />
            @error('postal_code')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="address">住所</label>
            <input
                class="p-form__control"
                type="text"
                id="address"
                name="address"
                value="{{ old('address', optional($profile)->addresses) }}"
                placeholder="例）東京都千代田区丸の内1-1-1"
            />
            @error('address')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="building_name">建物名</label>
            <input
                class="p-form__control"
                type="text"
                id="building_name"
                name="building_name"
                value="{{ old('building_name', optional($profile)->building_names) }}"
            />
            @error('building_name')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <button class="c-button c-button--primary p-form__submit" type="submit">
            更新する
        </button>
    </form>
    <script>
        (function () {
            const input = document.getElementById("avatar");
            const box = document.getElementById("js-avatar-preview");
            if (!input || !box) return;
            input.addEventListener("change", function (e) {
                const file = e.target.files && e.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function (ev) {
                    box.style.backgroundImage = "url(" + ev.target.result + ")";
                };
                reader.readAsDataURL(file);
            });
        })();
    </script>
    @endsection
</div>
