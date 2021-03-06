<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BrazeLandingMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $jobs;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($jobs)
    {
        //
        $this->jobs = $jobs;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.jobs.braze-landing-daily-notice')
                ->subject('[INFO] Interface Braze AVRO Landing Daily Notice : ' . date('Y-m-d'))
                ->with(['jobs' => $this->jobs]);
    }
}
