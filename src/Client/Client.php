<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Http\Exception\ClientException;
use Gadget\Http\Exception\RequestException;
use Gadget\Http\Exception\ResponseException;
use Gadget\Http\Message\MessageFactory;
use Gadget\Http\Message\MessageHandler;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class Client implements ClientInterface
{
    /**
     * @param ClientInterface $client
     * @param MessageFactory $messageFactory
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private ClientInterface $client,
        private MessageFactory $messageFactory,
        private array $middleware = []
    ) {
    }


    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return (new MiddlewareHandler($this->client, $this->middleware))
            ->handle($this->getMessageFactory()->toServerRequest($request));
    }


    /**
     * @template TResponse
     * @param MessageHandler<TResponse> $handler
     * @return TResponse
     */
    public function invoke(MessageHandler $handler): mixed
    {
        try {
            try {
                $request = $handler->setRequest($handler->createRequest($this->getMessageFactory()));
            } catch (\Throwable $t) {
                throw new RequestException("Error building request", 0, $t);
            }

            try {
                $response = $handler->setResponse($this->sendRequest($request));
            } catch (\Throwable $t) {
                throw new ClientException([
                    "Error sending request: %s %s",
                    $request->getMethod(),
                    $request->getUri()
                ], 0, $t);
            }

            try {
                return $handler->handleResponse($response);
            } catch (\Throwable $t) {
                throw new ResponseException([
                    "Error handling response: %s %s => %s",
                    $request->getMethod(),
                    $request->getUri(),
                    $response->getStatusCode()
                ], 0, $t);
            }
        } catch (\Throwable $t) {
            return $handler->handleError($t);
        }
    }


    /**
     * @return MessageFactory
     */
    public function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }


    /**
     * @param MessageFactory $messageFactory
     * @return static
     */
    public function setMessageFactory(MessageFactory $messageFactory): static
    {
        $this->messageFactory = $messageFactory;
        return $this;
    }


    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }


    /**
     * @param MiddlewareInterface[] $middleware
     * @return static
     */
    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }
}
