@extends('layouts.app') @section('title', 'メール認証') @push('styles')
<link rel="stylesheet" href="/css/email-auth.css" />
<link rel="stylesheet" href="/css/email-code-verification.css" />
@endpush @section('content')
<div class="p-auth">
    <h1 class="p-auth__title">メール認証</h1>

    <div class="p-auth__content">
        <div class="p-auth__user-info">
            <p class="p-auth__message">
                メールに送信された6桁の認証コードを入力してください
            </p>
        </div>

        <form
            action="{{ route('email.verify.execute') }}"
            method="post"
            class="p-form"
        >
            @csrf
            <div class="p-form__group">
                <label for="verification_code" class="p-form__label"
                    >認証コード</label
                >
                <input
                    type="text"
                    id="verification_code"
                    name="verification_code"
                    class="p-form__input @error('verification_code') p-form__input--error @enderror"
                    placeholder="123456"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    autocomplete="off"
                />
                @error('verification_code')
                <span class="p-form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="p-form__group">
                <button
                    type="submit"
                    class="c-button c-button--primary p-form__submit"
                >
                    認証を完了する
                </button>
            </div>
        </form>

        <div class="p-auth__help">
          
            <a href="{{ route('email.resend') }}" class="p-auth__resend-link">
                認証メールを再送信
            </a>
            <br />
            <a href="{{ route('email.guide') }}" class="p-auth__back-link">
                ← 認証ガイドに戻る
            </a>
        </div>
    </div>
</div>
@endsection
