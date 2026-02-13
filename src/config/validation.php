<?php

return [
    'register' => [
        'rules' => [
            'username' => ['required','string','max:20'],
            'email' => ['required','email','unique:users,email'],
            'password' => ['required','string','min:8','confirmed'],
            'password_confirmation' => ['required','string','min:8'],
        ],
        'messages' => [
            'username.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと一致しません',
            'password_confirmation.min' => 'パスワードは8文字以上で入力してください',
        ],
        'test_data' => [
            'valid_username' => 'テストユーザー',
            'valid_email' => 'test@example.com',
            'valid_password' => 'password123',
            'short_password' => '1234567', // 7文字（8文字未満）
            'different_password' => 'different_password',
        ],
    ],
    'login' => [
        'rules' => [
            'email' => ['required'],
            'password' => ['required'],
        ],
        'messages' => [
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
        ],
        'test_data' => [
            'valid_email' => 'test@example.com',
            'valid_password' => 'password123',
            'invalid_email' => 'invalid@example.com',
            'invalid_password' => 'wrongpassword',
            'invalid_format_email' => 'invalid-email-format',
        ],
    ],
];
