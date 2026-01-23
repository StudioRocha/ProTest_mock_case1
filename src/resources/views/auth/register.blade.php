@extends('layouts.app') @section('title', '会員登録') @section('content')
<div class="p-auth">
    <h1 class="p-auth__title">会員登録</h1>
    <form
        action="{{ route('register') }}"
        method="post"
        class="p-form"
        novalidate
    >
        @csrf
        <div class="p-form__group">
            <label class="p-form__label" for="username">ユーザー名</label>
            <input
                class="p-form__control"
                type="text"
                id="username"
                name="username"
                value="{{ old('username') }}"
            />
            @error('username')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="email">メールアドレス</label>
            <input
                class="p-form__control"
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
            />
            @error('email')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="password">パスワード</label>
            <input
                class="p-form__control"
                type="password"
                id="password"
                name="password"
            />
            @error('password')
                @if($message !== 'パスワードと一致しません')
                <div class="p-form__error">{{ $message }}</div>
                @endif
            @enderror
        </div>
        <div class="p-form__group">
            <label class="p-form__label" for="password_confirmation"
                >確認用パスワード</label
            >
            <input
                class="p-form__control"
                type="password"
                id="password_confirmation"
                name="password_confirmation"
            />
            @error('password_confirmation')
            <div class="p-form__error">{{ $message }}</div>
            @enderror
            @error('password')
                @if($message === 'パスワードと一致しません')
                <div class="p-form__error">{{ $message }}</div>
                @endif
            @enderror
        </div>
        <button class="c-button c-button--primary p-form__submit" type="submit">
            登録する
        </button>
    </form>
    <div class="p-auth__link">
        <a href="{{ route('login') }}">ログインはこちら</a>
    </div>
</div>
@endsection
