<?php

namespace BotMan\Drivers\AmazonAlexa;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use Techworker\Ssml\ContainerElement;
use BotMan\BotMan\Messages\Incoming\Answer;
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
     * @var Collection
     */
    private $headers;
    /**
     * @var false|resource|string|null
     */
    private $body;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->headers = Collection::make((array)$request->headers->all(), true);
        $this->payload = Collection::make((array)json_decode($request->getContent(), true));
        $this->event = Collection::make((array)$this->payload->get('request'));
        $this->config = Collection::make($this->config->get('amazon-alexa', []));
        $this->body = $request->getContent();
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
        return ($this->config->get('enableValidation') && $this->validate() || !$this->config->get('enableValidation'))
            ? $this->event->has('requestId') && $this->event->has('type')
            : false;
    }

    /**
     * @param IncomingMessage $message
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

            $message = new IncomingMessage($intent['name'], $session['user']['userId'], $session['sessionId'],
                $this->payload);
            if (!is_null($intent) && array_key_exists('slots', $intent)) {
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
        if ($this->config->get('enableValidation') && !$this->validate()) {
            return false;
        }
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
     * @return array
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
        if (is_string($payload['text'])) {
            $response->respondText($payload['text']);
        } elseif ($payload['text'] instanceof ContainerElement) {
            $response->respondSsml($payload['text']);
        }
        $response->card = $payload['card'] ?? null;
        $response->shouldEndSession = $payload['shouldEndSession'] ?? false;

        return Response::create(json_encode($response->render()))->send();
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        if (!$this->config->get('enableValidation')) {
            return true;
        } else {
            if (!empty($this->config->get('skillId'))) {
                return true;
            }
            return false;
        }
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


    public function dialogDelegate()
    {
        $response = [
            'version' => '1.0',
            'sessionAttributes' => [],
            'response' => [
                'outputSpeech' => null,
                'card' => null,
                'directives' => [
                    [
                        'type' => 'Dialog.Delegate'
                    ]
                ],
                'reprompt' => null,
                'shouldEndSession' => false,
            ]
        ];

        return Response::create(json_encode($response))->send();
    }

    /**
     * Validate the request
     * @return bool
     */
    private function validate()
    {
        try {
            $this->validateHeaders();
        } catch (\Exception $e) {
            return false;
        }

        try {
            $this->validateCertificate();
        } catch (\Exception $e) {
            return false;
        }

        try {
            $this->validateTimestamp();
        } catch (\Exception $e) {
            return false;
        }

        try {
            $this->validateSkillId();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Validate the certificate headers
     * @return bool
     * @throws \Exception
     */
    private function validateHeaders()
    {
        $chainUrl = $this->headers->get('signaturecertchainurl')[0];
        if (!isset($chainUrl)) {
            throw new \Exception('This request did not come from Amazon.');
        }

        $uriParts = parse_url($chainUrl);
        if (strcasecmp($uriParts['host'], 's3.amazonaws.com') !== 0) {
            throw new \Exception('The host for the Certificate provided in the header is invalid');
        }
        if (strpos($uriParts['path'], '/echo.api/') !== 0) {
            throw new \Exception('The URL path for the Certificate provided in the header is invalid');
        }
        if (strcasecmp($uriParts['scheme'], 'https') !== 0) {
            throw new \Exception('The URL is using an unsupported scheme. Should be https');
        }
        if (array_key_exists('port', $uriParts) && '443' !== $uriParts['port']) {
            throw new \Exception('The URL is using an unsupported https port');
        }

        return true;
    }

    /**
     * Validate the certificate
     * @return bool
     * @throws \Exception
     */
    private function validateCertificate()
    {
        $chainUrl = $this->headers->get('signaturecertchainurl')[0];
        $signature = $this->headers->get('signature')[0];
        $echoDomain = 'echo-api.amazon.com';
        $pem = file_get_contents($chainUrl);
        // Validate certificate chain and signature.
        $ssl_check = openssl_verify($this->body, base64_decode($signature), $pem, 'sha1');
        if (intval($ssl_check) !== 1) {
            throw new \Exception(openssl_error_string());
        }
        // Parse certificate for validations below.
        $parsed_certificate = openssl_x509_parse($pem);
        if (!$parsed_certificate) {
            throw new \Exception('x509 parsing failed');
        }
        // Check that the domain echo-api.amazon.com is present in
        // the Subject Alternative Names (SANs) section of the signing certificate.
        if (strpos($parsed_certificate['extensions']['subjectAltName'], $echoDomain) === false) {
            throw new \Exception('subjectAltName Check Failed');
        }
        // Check that the signing certificate has not expired
        // (examine both the Not Before and Not After dates).
        $valid_from = $parsed_certificate['validFrom_time_t'];
        $valid_to = $parsed_certificate['validTo_time_t'];
        $time = time();
        if (!($valid_from <= $time && $time <= $valid_to)) {
            throw new \Exception('certificate expiration check failed');
        }

        return true;
    }

    /**
     * Validate the request timestamp
     * @return bool
     * @throws \Exception
     */
    private function validateTimestamp()
    {
        $request = $this->payload->get('request');
        if (time() - strtotime($request['timestamp']) > 60) {
            throw new \Exception('Timestamp validation failure. Current time: ' . time() . ' vs. Timestamp: ' . $request['timestamp']);
        }
        return true;
    }

    /**
     * Validate the skill id given by the request
     * @return bool
     * @throws \Exception
     */
    private function validateSkillId()
    {
        $skillId = $this->payload->get('session')['application']['applicationId'];
        if ($this->config->get('skillId') !== $skillId) {
            throw new \Exception('Skill ID is not valid');
        }
        return true;
    }
}
