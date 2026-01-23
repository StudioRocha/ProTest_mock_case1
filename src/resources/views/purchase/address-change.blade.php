@extends('layouts.app') @push('styles')
<link rel="stylesheet" href="/css/address-change.css" />
@endpush @section('title', '住所の変更') @section('content')
<div class="p-address-change">
    <div class="p-address-change__container">
        <h1 class="p-address-change__title">住所の変更</h1>

        <form
            action="{{ route('address.update', $item) }}"
            method="post"
            class="p-address-change__form"
        >
            @csrf

            <div class="p-address-change__field">
                <label for="postal_code" class="p-address-change__label"
                    >郵便番号</label
                >
                <input type="text" id="postal_code" name="postal_code"
                class="p-address-change__input @error('postal_code')
                p-address-change__input--error @enderror" value="{{
                    old(
                        "postal_code",
                        str_replace(
                            "〒",
                            "",
                            explode("\n", $currentAddress)[0] ?? ""
                        )
                    )
                }}" placeholder="123-4567" maxlength="8" > @error('postal_code')
                <div class="p-address-change__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="p-address-change__field">
                <label for="address" class="p-address-change__label"
                    >住所</label
                >
                <input type="text" id="address" name="address"
                class="p-address-change__input @error('address')
                p-address-change__input--error @enderror" value="{{
                    old("address", explode("\n", $currentAddress)[1] ?? "")
                }}" placeholder="東京都渋谷区恵比寿1-2-3" > @error('address')
                <div class="p-address-change__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="p-address-change__field">
                <label for="building_name" class="p-address-change__label"
                    >建物名</label
                >
                <input type="text" id="building_name" name="building_name"
                class="p-address-change__input @error('building_name')
                p-address-change__input--error @enderror" value="{{
                    old(
                        "building_name",
                        explode("\n", $currentAddress)[2] ?? ""
                    )
                }}" placeholder="恵比寿マンション101号" >
                @error('building_name')
                <div class="p-address-change__error">{{ $message }}</div>
                @enderror
            </div>

            <div class="p-address-change__actions">
                <button type="submit" class="p-address-change__submit">
                    更新する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
