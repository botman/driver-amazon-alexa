<?php

namespace BotMan\Drivers\AmazonAlexa;

use Alexa\Response\Response;

class AlexaResponse extends Response
{

    const TYPE_PLAIN_TEXT = 'PlainText';

    const TYPE_SSML = 'SSML';

    public function respondText(string $text)
    {
        $this->outputSpeech = new AlexaOutputSpeech;
        $this->outputSpeech->type = self::TYPE_PLAIN_TEXT;
        $this->outputSpeech->text = $text;

        return $this;
    }

    public function respondSsml(string $ssml)
    {
        $this->outputSpeech = new AlexaOutputSpeech;
        $this->outputSpeech->type = self::TYPE_SSML;
        $this->outputSpeech->ssml = $ssml;

        return $this;
    }

}