<?php

namespace App\Channels\Messages;

class SmsMessage
{
    public $content;

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    public function content(string $content)
    {
        $this->content = trim($content);
        return $this;
    }
}