<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    // public $summary;
    public $yes;
    public $no;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($yes, $no, $subject)
    {
        $this->yes = $yes;
        $this->no = $no;
        $this->subject  = $subject;
    }


    public function build()
    {
        return $this->subject($this->subject)
            ->view('mail')
            ->with([
                'yes' => $this->yes,
                'no' => $this->no,
            ]);
    }
}