<?php

namespace Tests;

use BotMan\Drivers\AmazonAlexa\Exceptions\AmazonValidationException;
use Mockery as m;
use BotMan\BotMan\Http\Curl;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\AmazonAlexa\AmazonAlexaDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use Techworker\Ssml\Element\Audio;
use Techworker\Ssml\SsmlBuilder;

class AmazonAlexaDriverTest extends TestCase
{
    private function getDriver($responseData, $htmlInterface = null, $config = [])
    {
        $request = Request::create('', 'POST', [], [], [], [
            'Content-Type: application/json',
        ], $responseData);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new AmazonAlexaDriver($request, $config, $htmlInterface);
    }

    private function getValidDriver($htmlInterface = null, $type = 'IntentRequest', $config = [])
    {
        $responseData = '{
  "session": {
    "new": false,
    "sessionId": "session_id",
    "application": {
      "applicationId": "app_id"
    },
    "attributes": {},
    "user": {
      "userId": "alexa_user_id"
    }
  },
  "request": {
    "type": "' . $type . '",
    "requestId": "request_id",
    "intent": {
      "name": "intent_name",
      "slots": {
        "location": {
          "name": "location",
          "value": "Berlin"
        }
      }
    },
    "locale": "de-DE",
    "timestamp": "2017-09-27T20:50:37Z"
  },
  "context": {
    "System": {
      "user": {
        "userId": "amzn1.ask.account.AFFOLOKPQG7PBWPG3MCGT33GIDBXXPF4WUPWKVB354AG7XTGJTS5KWKQ2GWBW7CESN7WA77M6CJVBSTCUDSQD5JH7RPA7RTAK3GW6QIHZOKOOPCG73LG5HY6U4MGDOTJRMQAGABPBXTUJYZGKDT5BXRNRWH5DE6OHKYIPLFL54DGZEONN5TZ64UVW6BNB4JSUKICACVLEZV7WGI"
      }
    }
  },
  "version": "1.0"
}';

        return $this->getDriver($responseData, $htmlInterface, $config);
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver(null);
        $this->assertSame('AmazonAlexa', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver(null);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getValidDriver();
        $this->assertTrue($driver->matchesRequest());

        $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => false]]);
        $this->assertTrue($driver->matchesRequest());

        $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => true]]);
        $this->expectException(AmazonValidationException::class);
        $driver->matchesRequest();

        $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => true, 'skillId' => 'app_id']]);
        $this->expectException(AmazonValidationException::class);
        $driver->matchesRequest();
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $driver = $this->getValidDriver();
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_messages_by_reference()
    {
        $driver = $this->getValidDriver();
        $hash = spl_object_hash($driver->getMessages()[0]);

        $this->assertSame($hash, spl_object_hash($driver->getMessages()[0]));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $driver = $this->getValidDriver();
        $this->assertSame('intent_name', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_detects_bots()
    {
        $driver = $this->getValidDriver();
        $this->assertFalse($driver->isBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $driver = $this->getValidDriver();
        $this->assertSame('alexa_user_id', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $driver = $this->getValidDriver();
        $this->assertSame('session_id', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getValidDriver();

        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame($user->getId(), 'alexa_user_id');
        $this->assertNull($user->getFirstName());
        $this->assertNull($user->getLastName());
        $this->assertNull($user->getUsername());
    }

    /** @test */
    public function it_is_configured()
    {
        $driver = $this->getValidDriver();
        $this->assertTrue($driver->isConfigured());

        $driver = $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => false]]);
        $this->assertTrue($driver->isConfigured());

        $driver = $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => true]]);
        $this->assertFalse($driver->isConfigured());

        $driver = $driver = $this->getValidDriver(null, 'IntentRequest', ['amazon-alexa' => ['enableValidation' => true, 'skillId' => 'app_id']]);
        $this->assertTrue($driver->isConfigured());
    }

    /** @test */
    public function it_can_build_payload()
    {
        $driver = $this->getValidDriver();

        $incomingMessage = new IncomingMessage('text', '123456', '987654');

        $message = 'string';
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'text' => 'string',
        ], $payload);

        $message = new OutgoingMessage('message object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'text' => 'message object',
        ], $payload);

        $message = new Question('question object');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'text' => 'question object',
        ], $payload);

        $message = SsmlBuilder::factory()->text('This is SSML!');
        $payload = $driver->buildServicePayload($message, $incomingMessage);

        $this->assertSame([
            'text' => $message
        ], $payload);
    }

    /** @test */
    public function it_can_send_payload()
    {
        $driver = $this->getValidDriver();

        $ssml = SsmlBuilder::factory();
        $ssml->text('This is SSML!')->audio('foo')->up()->text('more Text');
        $payload = [
            'text' => $ssml,
        ];

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $this->assertSame('{"version":"1.0","sessionAttributes":[],"response":{"outputSpeech":{"type":"SSML","ssml":"<speak>This is SSML!<audio src=\"foo\"\/>more Text<\/speak>"},"card":null,"reprompt":null,"shouldEndSession":false}}',
            $response->getContent());
    }

    /** @test */
    public function it_can_send_ssml_payload()
    {
        $driver = $this->getValidDriver();

        $payload = [
            'text' => 'string',
        ];

        /** @var Response $response */
        $response = $driver->sendPayload($payload);
        $this->assertSame('{"version":"1.0","sessionAttributes":[],"response":{"outputSpeech":{"type":"PlainText","text":"string"},"card":null,"reprompt":null,"shouldEndSession":false}}',
            $response->getContent());
    }

    /** @test */
    public function it_fires_launch_event()
    {
        $driver = $this->getValidDriver(null, AmazonAlexaDriver::LAUNCH_REQUEST);

        $event = $driver->hasMatchingEvent();

        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertSame(AmazonAlexaDriver::LAUNCH_REQUEST, $event->getName());
    }

    /** @test */
    public function it_fires_session_ended_event()
    {
        $driver = $this->getValidDriver(null, AmazonAlexaDriver::SESSION_ENDED_REQUEST);

        $event = $driver->hasMatchingEvent();

        $this->assertInstanceOf(GenericEvent::class, $event);
        $this->assertSame(AmazonAlexaDriver::SESSION_ENDED_REQUEST, $event->getName());
    }

    /** @test */
    public function it_no_events_for_regular_messages()
    {
        $driver = $this->getValidDriver();

        $this->assertFalse($driver->hasMatchingEvent());
    }

    /** @test */
    public function it_can_get_conversation_answers()
    {
        $driver = $this->getValidDriver();

        $incomingMessage = new IncomingMessage('text', '123456', '987654');
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertSame('text', $answer->getText());
    }
}
