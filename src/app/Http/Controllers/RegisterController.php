<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class RegisterController extends Controller
{
    protected $createNewUser;

    public function __construct(CreateNewUser $createNewUser)
    {
        $this->createNewUser = $createNewUser;
    }

    public function show()
    {
        return View::make('auth.register');
    }

    public function store(Request $request)
    {
        // ユーザー作成処理を実行
        $result = $this->createNewUser->create($request->all());
        
        // リダイレクトレスポンスの場合はそのまま返す
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }
        
        // 通常の場合はメール認証誘導画面にリダイレクト
        return redirect()->route('email.guide')->with('success', '登録が完了しました。メール認証を完了してください。');
    }
}


