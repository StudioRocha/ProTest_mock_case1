@extends('layouts.app') @section('title', 'ログイン') @push('styles')
<link rel="stylesheet" href="/css/auth.css" />
@endpush @section('content')
<div class="p-auth">
    <h1 class="p-auth__title">ログイン</h1>
    <form action="{{ route('login') }}" method="post" class="p-form" novalidate>
        @csrf
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
            <div class="p-form__error">{{ $message }}</div>
            @enderror
        </div>
        <button class="c-button c-button--primary p-form__submit" type="submit">
            ログイン
        </button>
    </form>
    <div class="p-auth__link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
    @endsection
</div>
