<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacebookOfflineConvertionErrorMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($detail)
    {
        $this->detail = $detail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.Facebook.FacebookOfflineConvertionError')
                ->from("korapotu@gmail.com", "FacebookOfflineConvertion")
                ->subject('[INFO] Facebook Offline Convertion Error : ' . date('Y-m-d'))
                ->with(['detail' => $this->detail]);
      
    }
}
