<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>取引が完了しました</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h1 style="color: #e60033; margin-top: 0;">取引が完了しました</h1>
        
        <p>こんにちは、{{ $userName }}さん</p>
        
        <p>以下の商品の取引が完了しました。</p>
        
        <div style="background-color: #fff; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #e60033;">
            <h2 style="margin-top: 0; color: #111;">{{ $itemName }}</h2>
            <p style="margin: 5px 0;"><strong>価格:</strong> ¥{{ number_format($itemPrice) }}</p>
            <p style="margin: 5px 0;"><strong>取引相手:</strong> {{ $otherUserName }}さん</p>
        </div>
        
        <p>取引チャット画面から、取引相手への評価をお願いいたします。</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $chatUrl }}" style="display: inline-block; background-color: #e60033; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">取引チャット画面を開く</a>
        </div>
        
        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            このメールは自動送信されています。<br>
            ご不明な点がございましたら、お問い合わせください。
        </p>
    </div>
</body>
</html>
