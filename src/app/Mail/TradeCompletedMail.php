<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\User;

class TradeCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $item;
    public $user;
    public $otherUser;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, User $user, User $otherUser)
    {
        $this->order = $order;
        $this->item = $order->item;
        $this->user = $user;
        $this->otherUser = $otherUser;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $userName = optional($this->user->profile)->usernames ?? $this->user->name;
        $otherUserName = optional($this->otherUser->profile)->usernames ?? $this->otherUser->name;
        
        // チャットアクセストークンを生成（24時間有効）
        $token = Str::random(60);
        $expiresAt = now()->addHours(24);
        
        $this->order->update([
            'chat_access_token' => $token,
            'chat_access_token_expires_at' => $expiresAt,
        ]);
        
        $chatUrl = route('chat.access', ['item' => $this->item->id, 'token' => $token]);

        return $this->subject('取引が完了しました')
                    ->view('emails.trade_completed')
                    ->with([
                        'userName' => $userName,
                        'otherUserName' => $otherUserName,
                        'itemName' => $this->item->item_names,
                        'itemPrice' => $this->item->item_prices,
                        'chatUrl' => $chatUrl,
                    ]);
    }
}
