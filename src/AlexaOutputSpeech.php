<?php

namespace BotMan\Drivers\AmazonAlexa;

use Illuminate\Support\Collection;

class AlexaOutputSpeech {
    const TYPE_SSML = 'SSML';
    const TYPE_PLAIN_TEXT = 'PlainText';

    public $type = self::TYPE_PLAIN_TEXT;

    public $text = null;

    public $ssml = null;

    public function render() {
        return Collection::make([
            'type' => $this->type,
            'text' => $this->text,
            'ssml' => $this->ssml,
        ])->filter()->toArray();
    }
}