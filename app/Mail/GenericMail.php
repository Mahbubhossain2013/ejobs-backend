<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $viewName;
    public array $payload;

    public function __construct(string $viewName, array $payload = [], string $subject = '')
    {
        $this->viewName = $viewName;
        $this->payload = $payload;
        $this->subject($subject);
    }

    public function build()
    {
        return $this->view($this->viewName, $this->payload);
    }
}