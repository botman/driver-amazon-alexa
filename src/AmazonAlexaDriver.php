<?php

namespace BotMan\Drivers\AmazonAlexa;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use Alexa\Response\Response as AlexaResponse;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Drivers\Events\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\AmazonAlexa\Extensions\Card;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class AmazonAlexaDriver extends HttpDriver
{
    const DRIVER_NAME = 'AmazonAlexa';
    const LAUNCH_REQUEST = 'LaunchRequest';
    const SESSION_ENDED_REQUEST = 'SessionEndedRequest';

    protected $messages = [];

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = Collection::make((array) json_decode($request->getContent(), true));
        $this->event = Collection::make((array) $this->payload->get('request'));
        $this->config = Collection::make($this->config->get('amazon-alexa', []));
    }

    /**
     * @param IncomingMessage $matchingMessage
     * @return \BotMan\BotMan\Users\User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return $this->event->has('requestId') && $this->event->has('type');
    }

    /**
     * @param  IncomingMessage $message
     * @return \BotMan\BotMan\Messages\Incoming\Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        if (empty($this->messages)) {
            $intent = $this->event->get('intent');
            $session = $this->payload->get('session');

            $message = new IncomingMessage($intent['name'], $session['user']['userId'], $session['sessionId']);
            if (! is_null($intent) && array_key_exists('slots', $intent)) {
                $message->addExtras('slots', Collection::make($intent['slots']));
            }
            $this->messages = [$message];
        }

        return $this->messages;
    }

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        $type = $this->event->get('type');
        if ($type === self::LAUNCH_REQUEST || $type === self::SESSION_ENDED_REQUEST) {
            $event = new GenericEvent($this->event);
            $event->setName($type);

            return $event;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isBot()
    {
        return false;
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = $additionalParameters;

        if ($message instanceof Question) {
            $text = $message->getText();
        } elseif ($message instanceof OutgoingMessage) {
            $text = $message->getText();
            $attachment = $message->getAttachment();
            if ($attachment instanceof Card) {
                $parameters['card'] = $attachment;
            }
        } else {
            $text = $message;
        }

        $parameters['text'] = $text;

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        $response = new AlexaResponse();
        $response->respond($payload['text']);
        $response->card = $payload['card'] ?? null;
        $response->shouldEndSession = $payload['shouldEndSession'] ?? false;

        return Response::create(json_encode($response->render()))->send();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return true;
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param IncomingMessage $matchingMessage
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        //
    }
}
