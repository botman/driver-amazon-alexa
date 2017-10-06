<?php

namespace BotMan\Drivers\AmazonAlexa\Extensions;

use BotMan\BotMan\Messages\Attachments\Attachment;

class Card extends Attachment
{
    const DEFAULT_CARD_TYPE = 'Simple';
    const LINK_ACCOUNT_CARD_TYPE = 'LinkAccount';
    const STANDARD_CARD_TYPE = 'Standard';
    const SIMPLE_CARD_TYPE = 'Simple';

    /** @var string */
    protected $type = self::DEFAULT_CARD_TYPE;
    /** @var string */
    protected $title = '';
    /** @var string */
    protected $subtitle;
    /** @var string */
    protected $text;
    /** @var array Only for standard card types */
    protected $image;

    /**
     * @param $title
     * @param string $subtitle
     * @return Card
     */
    public static function create($title, $subtitle = '')
    {
        return new self($title, $subtitle);
    }

    /**
     * Card constructor.
     * @param $title
     * @param $subtitle
     */
    public function __construct($title, $subtitle)
    {
        parent::__construct($title);

        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    /**
     * Set the card title.
     *
     * @param $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the card subtitle.
     *
     * @param $subtitle
     * @return $this
     */
    public function subtitle($subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Set the card type.
     *
     * @param $type
     * @return $this
     */
    public function type($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set the card content.
     *
     * @param $text
     * @return $this
     */
    public function text($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Set the card image.
     *
     * @param $large
     * @param null $small
     * @return $this
     */
    public function image($large, $small = null)
    {
        $this->image = [
            'smallImageUrl' => $small ?? $large,
            'largeImageUrl' => $large,
        ];

        return $this;
    }

    /**
     * @return array|string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [];
    }

    /**
     * @return array
     */
    public function render()
    {
        return array_filter([
            'type' => $this->type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'content' => $this->text,
            'text' => $this->text,
            'image' => $this->image,
        ]);
    }
}
