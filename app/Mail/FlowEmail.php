<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class FlowEmail extends Mailable
{
    public function __construct(
        public string $flowSubject,
        public string $flowBody
    ) {}

    public function build()
    {
        return $this->subject($this->flowSubject)
                    ->view('emails.flow')
                    ->with([
                        'body' => $this->flowBody
                    ]);
    }
}
