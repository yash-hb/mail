<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Send12NoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public $summary;
    public $subject;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($summary, $subject)
    {
        $this->summary = $summary;
        $this->subject = $subject;
    }



    public function build()
    {
        return $this->subject($this->subject)
            ->view('mail2')
            ->with($this->summary);
    }
}