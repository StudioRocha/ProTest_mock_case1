<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>アカウント切り替えの確認</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <style>
        .confirm-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .confirm-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .confirm-message {
            margin-bottom: 30px;
            line-height: 1.8;
            color: #666;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .user-info strong {
            color: #e60033;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }
        .btn-primary {
            background-color: #e60033;
            color: #fff;
        }
        .btn-primary:hover {
            background-color: #cc0029;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="confirm-container">
        <h1 class="confirm-title">アカウント切り替えの確認</h1>
        
        <div class="confirm-message">
            @if(Auth::check())
                <p>現在、<strong>{{ optional(Auth::user()->profile)->usernames ?? Auth::user()->name }}</strong>さん（{{ Auth::user()->email }}）でログインしています。</p>
                <p>この取引チャットにアクセスするには、<strong>{{ optional($targetUser->profile)->usernames ?? $targetUser->name }}</strong>さん（{{ $targetUser->email }}）でログインする必要があります。</p>
            @else
                <p>この取引チャットにアクセスするには、<strong>{{ optional($targetUser->profile)->usernames ?? $targetUser->name }}</strong>さん（{{ $targetUser->email }}）でログインする必要があります。</p>
            @endif
            
            <div class="user-info">
                <p><strong>商品名:</strong> {{ $order->item->item_names }}</p>
                <p><strong>取引相手:</strong> {{ optional($order->user->profile)->usernames ?? $order->user->name }}さん</p>
            </div>
            
            <p>ログアウトして、正しいアカウントでログインしますか？</p>
        </div>
        
        <div class="button-group">
            <form action="{{ route('chat.switch-account', ['item' => $item->id, 'token' => $token]) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">ログアウトして続行</button>
            </form>
            <a href="{{ route('items.index') }}" class="btn btn-secondary">キャンセル</a>
        </div>
    </div>
</body>
</html>
