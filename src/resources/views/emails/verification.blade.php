<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>メール認証を完了してください</title>
    </head>
    <body>
        <h2>メール認証を完了してください</h2>

        <p>{{ $user->name }} 様</p>

        <p>ご登録いただき、ありがとうございます。</p>

        <p>以下のボタンをクリックしてメール認証を完了してください：</p>

        <div style="text-align: center; margin: 30px 0">
            <a
                href="{{ $verificationUrl }}"
                style="
                    display: inline-block;
                    padding: 15px 30px;
                    background-color: #007bff;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    font-size: 16px;
                "
            >
                メール認証を完了する
            </a>
        </div>

        <p>認証リンクの有効期限は24時間です。</p>

        <p>ボタンがクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください：</p>

        <div
            style="
                background-color: #f5f5f5;
                padding: 15px;
                word-break: break-all;
                font-family: monospace;
                margin: 20px 0;
            "
        >
            <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
        </div>

        <p>
            もしこのメールに心当たりがない場合は、このメールを無視してください。
        </p>

        <hr />
        <p style="font-size: 12px; color: #666">
            このメールは自動送信されています。返信はできません。
        </p>
    </body>
</html>
