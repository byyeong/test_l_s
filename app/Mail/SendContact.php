<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;

class SendContact extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //print_r($this->data);
        //exit(1);
        $title = '';
        if ($this->data['job'] == 'findPassword') {
            $title = '비밀번호 재설정하기';
        }

        $result = $this->to($this->data['to'])
            ->with([
                'url' => $this->data['url']
            ])
            ->view('mail.contact')
            ->subject($title)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->replyTo(config('mail.from.address'), config('mail.from.name'));

        return $result;
        //return $this->view('view.name');
    }
}
