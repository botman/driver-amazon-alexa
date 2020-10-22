<?php

namespace BotMan\Drivers\AmazonAlexa\Extensions;

class Stream
{
    const DEFAULT_OFFSET_IN_MILLISECONDS = 0;

    protected $url;
    protected $token;
    protected $expectedPreviousToken;
    protected $offsetInMilliseconds = self::DEFAULT_OFFSET_IN_MILLISECONDS;

    public function url($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function token($token)
    {
        $this->token = $token;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function expectedPreviousToken($expectedPreviousToken)
    {
        $this->expectedPreviousToken = $expectedPreviousToken;

        return $this;
    }

    public function getExpectedPreviousToken()
    {
        return $this->expectedPreviousToken;
    }

    public function offsetInMilliseconds($offsetInMilliseconds)
    {
        $this->offsetInMilliseconds = $offsetInMilliseconds;

        return $this;
    }

    public function getOffsetInMilliseconds()
    {
        return $this->offsetInMilliseconds;
    }

    public function renderStream()
    {
        return [
            'url' => $this->url,
            'token' => $this->token,
            'expectedPreviousToken' => $this->expectedPreviousToken,
            'offsetInMilliseconds' => $this->offsetInMilliseconds,
        ];
    }
}