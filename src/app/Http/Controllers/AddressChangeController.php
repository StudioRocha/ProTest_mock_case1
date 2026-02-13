<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ProfileRequest;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AddressChangeController extends Controller
{
    /**
     * 送付先住所変更画面を表示
     */
    public function show(Item $item)
    {
        $user = Auth::user();
        
        // 商品が既に売却済みでないかチェック
        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は既に売却済みです。');
        }
        
        // 自分が出品した商品は購入不可
        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $item)->with('error', '自分が出品した商品は購入できません。');
        }
        
        $profile = $user->profile;
        
        // 初期住所を設定（プロフィールから取得）
        $defaultAddress = '';
        if ($profile) {
            $defaultAddress = "〒{$profile->postal_codes}\n{$profile->addresses}";
            if ($profile->building_names) {
                $defaultAddress .= "\n{$profile->building_names}";
            }
        }
        
        // セッションから変更された住所を取得（あれば）
        $currentAddress = Session::get("shipping_address_{$item->id}", $defaultAddress);
        
        return view('purchase.address-change', compact('item', 'currentAddress'));
    }
    
    /**
     * 送付先住所を更新
     */
    public function update(ProfileRequest $request, Item $item)
    {
        $user = Auth::user();
        
        // 商品が既に売却済みでないかチェック
        if ($item->is_sold) {
            return redirect()->route('items.index')->with('error', 'この商品は既に売却済みです。');
        }
        
        // 自分が出品した商品は購入不可
        if ($item->user_id === $user->id) {
            return redirect()->route('items.show', $item)->with('error', '自分が出品した商品は購入できません。');
        }
        
        // セッションに変更された住所を保存
        $newAddress = "〒{$request->postal_code}\n{$request->address}";
        if ($request->building_name) {
            $newAddress .= "\n{$request->building_name}";
        }
        
        Session::put("shipping_address_{$item->id}", $newAddress);
        
        return redirect()->route('items.purchase.form', $item)->with('success', '送付先住所を変更しました。');
    }
}
