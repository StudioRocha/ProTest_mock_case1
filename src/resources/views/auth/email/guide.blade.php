@extends('layouts.app') @push('styles')
<link rel="stylesheet" href="/css/email-auth.css" />
@endpush @section('title', 'メール認証') @section('content')
<div class="p-email-auth">
    <div class="p-email-auth__container">
        <div class="p-email-auth__message">
            <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
            <p>メール認証を完了してください。</p>
        </div>

        <div class="p-email-auth__actions">
            <a
                href="http://localhost:8025"
                target="_blank"
                rel="noopener noreferrer"
                class="p-email-auth__auto-btn"
            >
                認証はこちらから
            </a>

            @if(config('app.auto_verify_enabled', false))
            <a
                href="{{ route('email.auto-verify') }}"
                class="p-email-auth__auto-btn"
            >
                MailHog経由で自動認証(開発環境用)
            </a>
            @endif

            <a
                href="{{ route('email.resend') }}"
                class="p-email-auth__resend-link"
            >
                認証メールを再送する
            </a>
        </div>
    </div>
</div>
@endsection
