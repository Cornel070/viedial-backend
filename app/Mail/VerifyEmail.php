<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    private $verifyCode;

    private $name;

    public function __construct($code, $name)
    {
        $this->verifyCode = $code;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@viedial.com', 'Viedial Healthcare')
                ->markdown('emails.verify', [
                    'code' => $this->verifyCode,
                    'name' => $this->name,
                ]);
    }
}
