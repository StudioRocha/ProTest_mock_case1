<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>@yield('title', 'COACHTECH Flea')</title>
        <link rel="stylesheet" href="/css/sanitize.css" />
        <link
            href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
            rel="stylesheet"
            crossorigin="anonymous"
        />
        <link rel="stylesheet" href="/css/common.css" />
        @stack('styles')
    </head>
    <body
        class="l-body @if(request()->routeIs('email.guide') || request()->routeIs('email.verify.code')) email-auth-page @endif"
    >
        <header class="l-header">
            <div class="l-header__inner">
                <div class="p-header">
                    @php($isAuthPage = request()->routeIs('login') ||
                    request()->routeIs('register'))
                    @php($isChatPage = request()->routeIs('chat.*'))
                    <div class="p-header__left">
                        <a href="/" class="p-header__logo">
                            <img
                                src="{{ asset('images/logo.svg') }}"
                                alt="COACHTECH"
                                class="p-header__logo-img"
                            />
                        </a>
                    </div>
                    @unless($isAuthPage || $isChatPage)
                    <div class="p-header__center">
                        <form
                            action="{{ route('items.index') }}"
                            method="get"
                            class="p-header__search c-search"
                        >
                            <input
                                type="hidden"
                                name="tab"
                                value="{{ request('tab', 'recommend') }}"
                            />
                            <div class="input-group">
                                <input
                                    class="form-control c-search__input"
                                    type="text"
                                    name="keyword"
                                    value="{{ old('keyword', $keyword ?? '') }}"
                                    placeholder="なにをお探しですか？"
                                />
                                <button
                                    class="btn btn-dark c-search__button"
                                    type="submit"
                                >
                                    検索
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="p-header__right">
                        <nav class="p-header__nav c-nav">
                            @auth
                            <form
                                method="POST"
                                action="/logout"
                                class="p-header__auth"
                            >
                                @csrf
                                <button type="submit" class="c-nav__link">
                                    ログアウト
                                </button>
                            </form>
                            @else
                            <a class="c-nav__link" href="/login">ログイン</a>
                            @endauth
                            <a class="c-nav__link" href="/mypage">マイページ</a>
                            <a
                                class="c-button c-button--primary btn btn-dark"
                                href="/sell"
                                >出品</a
                            >
                        </nav>
                    </div>
                    @endunless
                    @if($isChatPage)
                    <div class="p-header__center"></div>
                    <div class="p-header__right"></div>
                    @endif
                </div>
            </div>
        </header>

        <main class="l-main @if($isChatPage) l-main--chat @endif">
            <div class="l-container @if($isChatPage) l-container--chat @endif">
                @unless($isChatPage)
                @include('components.flash')
                @endunless
                @yield('content')
            </div>
        </main>
        @stack('scripts')
    </body>
</html>
