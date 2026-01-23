<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'comment_body' => ['required','string','max:255'],
        ];
    }

    public function attributes()
    {
        return [
            'comment_body' => '商品コメント',
        ];
    }
}


