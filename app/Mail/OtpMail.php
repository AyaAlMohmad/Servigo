<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($code, $type)
    {
        $this->code = $code;
        $this->type = $type;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('رمز التحقق OTP - Servigo')
                    ->view('emails.otp');
    }
}