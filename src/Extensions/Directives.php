<?php

namespace BotMan\Drivers\AmazonAlexa\Extensions;

use BotMan\BotMan\Messages\Attachments\Attachment;

class Directives extends Attachment
{
    const DEFAULT_PLAY_TYPE = 'AudioPlayer.Play';
    const PLAY_PLAY_TYPE = 'AudioPlayer.Play';
    const STOP_PLAY_TYPE = 'AudioPlayer.Stop';
    const CLEAR_PLAY_TYPE = 'AudioPlayer.Clear-Queue';

    const DEFAULT_PLAY_BEHAVIOUR_TYPE = 'ENQUEUE';
    const ENQUEUE_PLAY_BEHAVIOUR_TYPE = 'ENQUEUE';
    const REPLACE_PLAY_BEHAVIOUR_TYPE = 'REPLACE_ALL';
    const REPLACE_ENQUEUE_BEHAVIOUR_TYPE = 'REPLACE_ENQUEUED';

    protected $type = self::DEFAULT_PLAY_TYPE;
    protected $playBehaviour = self::DEFAULT_PLAY_BEHAVIOUR_TYPE;
    protected $audioItem;

    public static function create()
    {
        return new self();
    }

    public function __construct()
    {
        $this->audioItem = new Stream;
    }

    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function playBehaviour($playBehaviour)
    {
        $this->playBehaviour = $playBehaviour;

        return $this;
    }

    public function getPlayBehaviour()
    {
        return $this->playBehaviour;
    }

    public function url($url)
    {
        $this->audioItem->url($url);
    }

    public function token($token)
    {
        $this->audioItem->token($token);
    }

    public function expectedPreviousToken($expectedPreviousToken)
    {
        $this->audioItem->expectedPreviousToken($expectedPreviousToken);
    }

    public function offsetInMilliseconds($offsetInMilliseconds)
    {
        $this->audioItem->offsetInMilliseconds($offsetInMilliseconds);
    }

    public function toWebDriver()
    {
        return [];
    }

    public function render()
    {
        return array_filter([
            [
                'type' => $this->type,
                'playBehavior' => $this->playBehaviour,
                'audioItem' => [
                    'stream' => $this->audioItem->renderStream(),
                ],
            ],
        ]);
    }
}