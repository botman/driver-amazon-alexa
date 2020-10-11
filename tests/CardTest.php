<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use BotMan\Drivers\AmazonAlexa\Extensions\Card;

class CardTest extends TestCase
{
    /** @test */
    public function it_can_be_created()
    {
        $card = new Card('title', 'subtitle');

        $output = $card->render();
        $this->assertSame('title', $output['title']);
        $this->assertSame('subtitle', $output['subtitle']);
        $this->assertSame(Card::DEFAULT_CARD_TYPE, $output['type']);
    }

    /** @test */
    public function it_can_change_title()
    {
        $card = new Card('title', 'subtitle');
        $card->title('modified');

        $output = $card->render();
        $this->assertSame('modified', $output['title']);
    }

    /** @test */
    public function it_can_change_subtitle()
    {
        $card = new Card('title', 'subtitle');
        $card->subtitle('modified');

        $output = $card->render();
        $this->assertSame('modified', $output['subtitle']);
    }

    /** @test */
    public function it_can_change_type()
    {
        $card = new Card('title', 'subtitle');
        $card->type(Card::LINK_ACCOUNT_CARD_TYPE);

        $output = $card->render();
        $this->assertSame(Card::LINK_ACCOUNT_CARD_TYPE, $output['type']);
    }

    /** @test */
    public function it_can_change_text()
    {
        $card = new Card('title', 'subtitle');
        $card->text('My custom alexa card text');

        $output = $card->render();
        $this->assertSame('My custom alexa card text', $output['text']);
        $this->assertSame('My custom alexa card text', $output['content']);
    }

    /** @test */
    public function it_can_change_image()
    {
        $card = new Card('title', 'subtitle');
        $card->image('large');

        $output = $card->render();
        $this->assertSame('large', $output['image']['smallImageUrl']);
        $this->assertSame('large', $output['image']['largeImageUrl']);

        $card = new Card('title', 'subtitle');
        $card->image('large', 'small');

        $output = $card->render();
        $this->assertSame('small', $output['image']['smallImageUrl']);
        $this->assertSame('large', $output['image']['largeImageUrl']);
    }
}
