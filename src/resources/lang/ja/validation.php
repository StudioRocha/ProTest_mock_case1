<?php

return [
    'accepted' => ':attribute を承認してください。',
    'active_url' => ':attribute は有効なURLではありません。',
    'after' => ':attribute には :date 以降の日付を指定してください。',
    'after_or_equal' => ':attribute には :date 以降の日付を指定してください。',
    'alpha' => ':attribute は英字のみがご利用できます。',
    'alpha_dash' => ':attribute は英数字とダッシュ(-)及び下線(_)がご利用できます。',
    'alpha_num' => ':attribute は英数字がご利用できます。',
    'array' => ':attribute は配列でなくてはなりません。',
    'before' => ':attribute には :date 以前の日付をご利用ください。',
    'before_or_equal' => ':attribute には :date 以前の日付をご利用ください。',
    'between' => [
        'numeric' => ':attribute は :min から :max までの数字で指定してください。',
        'file' => ':attribute のサイズは :min KBから :max KBの間で指定してください。',
        'string' => ':attribute は :min 文字から :max 文字の間で指定してください。',
        'array' => ':attribute は :min 個から :max 個の間で指定してください。',
    ],
    'boolean' => ':attribute には true か false を指定してください。',
    'confirmed' => ':attribute と確認用 :attribute が一致しません。',
    'date' => ':attribute は正しい日付ではありません。',
    'date_format' => ':attribute の形式は :format と一致しません。',
    'different' => ':attribute と :other には異なる値を指定してください。',
    'digits' => ':attribute は :digits 桁で指定してください。',
    'digits_between' => ':attribute は :min から :max 桁で指定してください。',
    'email' => ':attribute には有効なメールアドレスを指定してください。',
    'exists' => '選択された :attribute は正しくありません。',
    'file' => ':attribute にはファイルを指定してください。',
    'filled' => ':attribute は必須です。',
    'image' => ':attribute には画像ファイルを指定してください。',
    'in' => '選択された :attribute は正しくありません。',
    'integer' => ':attribute は整数で指定してください。',
    'mimes' => ':attribute のファイル形式は :values を指定してください。',
    'mimetypes' => ':attribute のファイル形式は :values を指定してください。',
    'min' => [
        'numeric' => ':attribute は :min 以上で指定してください。',
        'file' => ':attribute のサイズは :min KB以上で指定してください。',
        'string' => ':attribute は :min 文字以上で指定してください。',
        'array' => ':attribute は :min 個以上で指定してください。',
    ],
    'max' => [
        'numeric' => ':attribute は :max 以下で指定してください。',
        'file' => ':attribute のサイズは :max KB以下で指定してください。',
        'string' => ':attribute は :max 文字以内で指定してください。',
        'array' => ':attribute は :max 個以下で指定してください。',
    ],
    'not_in' => '選択された :attribute は正しくありません。',
    'numeric' => ':attribute は数値で指定してください。',
    'present' => ':attribute は必須です。',
    'regex' => ':attribute の形式が正しくありません。',
    'required' => ':attribute は必須です。',
    'required_with' => ':values を指定する場合は、:attribute も指定してください。',
    'same' => ':attribute と :other が一致しません。',
    'size' => [
        'numeric' => ':attribute は :size を指定してください。',
        'file' => ':attribute のサイズは :size KBで指定してください。',
        'string' => ':attribute は :size 文字で指定してください。',
        'array' => ':attribute は :size 個で指定してください。',
    ],
    'string' => ':attribute は文字列で指定してください。',
    'unique' => ':attribute は既に使用されています。',
    'url' => ':attribute の形式が正しくありません。',

    'custom' => [
        'postal_code' => [
            'regex' => '郵便番号は ハイフンありの8文字（123-4567)の形式で入力してください。',
        ],
    ],

    'attributes' => [
        // 共通フィールド名の日本語化（Fortifyのログイン等で使用）
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => '確認用パスワード',
        'username' => 'ユーザー名',
    ],
];


