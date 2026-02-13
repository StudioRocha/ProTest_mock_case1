<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'item_image' => ['required','mimes:jpeg,png'],
            'category_ids' => ['required','array','min:1'],
            'category_ids.*' => ['integer','exists:categories,id'],
            'condition' => ['required','in:1,2,3,4'],
            'item_name' => ['required','string'],
            'brand_name' => ['nullable','string'],
            'item_description' => ['required','string','max:255'],
            'item_price' => ['required','integer','min:0'],
        ];
    }

    public function attributes()
    {
        return [
            'item_image' => '商品画像',
            'category_ids' => '商品のカテゴリー',
            'condition' => '商品の状態',
            'item_name' => '商品名',
            'brand_name' => 'ブランド名',
            'item_description' => '商品説明',
            'item_price' => '販売価格',
        ];
    }
}


