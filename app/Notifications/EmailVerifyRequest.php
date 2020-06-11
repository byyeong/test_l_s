<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerifyRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('이메일 인증')
            ->markdown('mail.emailVerify', ['link' => $this->verificationUrl($notifiable)]);
    }

    protected function verificationUrl($notifiable)
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            ['id' => $notifiable->getKey()]
        );
        $user = request()->user();
        $token_ex = explode('=', $url);
        $last = \sizeof( $token_ex) - 1;
        $token = $token_ex[$last];
        DB::table('users')->where('id', $user->id)->update([
            'email_token' => $token
        ]);

        $url = str_replace('api.loda.travel', 'loda.travel', $url);

        return $url;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
