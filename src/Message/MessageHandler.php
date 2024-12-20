<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Gadget\Http\Client\ClientInterface;
use Gadget\Http\Exception\ClientException;
use Gadget\Http\Exception\RequestException;
use Gadget\Http\Exception\ResponseException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template TRequest
 * @template TResponse
 * @implements MessageHandlerInterface<TRequest,TResponse>
 */
abstract class MessageHandler implements MessageHandlerInterface
{
    /**
     * @var MessageHandlerContext<TRequest> $context
     */
    private MessageHandlerContext $context;


    /**
     * @param ClientInterface $client
     * @param TRequest|null $requestBody
     * @return TResponse
     */
    final public function invoke(
        ClientInterface $client,
        mixed $requestBody = null
    ): mixed {
        try {
            try {
                $this->context = $this->createContext($client, $requestBody);
            } catch (\Throwable $t) {
                throw new \RuntimeException("createContext()", 0, $t);
            }

            try {
                $request = $this->createRequest($this->context->getClient()->createRequestBuilder());
                $this->context->setRequest($request);
            } catch (\Throwable $t) {
                throw new RequestException($t);
            }

            try {
                $response = $this->sendRequest(
                    $this->context->getClient(),
                    $this->context->getRequest()
                );
                $this->context->setResponse($response);
            } catch (\Throwable $t) {
                throw new ClientException($request, $t);
            }

            try {
                $responseBody = $this->handleResponse($this->getContext()->getResponse());
            } catch (\Throwable $t) {
                throw new ResponseException($request, $response, $t);
            }
        } catch (\Throwable $t) {
            $responseBody = $this->handleError($t);
        }

        return $responseBody;
    }


    /**
     * @return MessageHandlerContext<TRequest>
     */
    protected function getContext(): MessageHandlerContext
    {
        return $this->context;
    }


    /**
     * @param ClientInterface $client
     * @param TRequest|null $requestBody
     * @return MessageHandlerContext<TRequest>
     */
    protected function createContext(
        ClientInterface $client,
        mixed $requestBody
    ): MessageHandlerContext {
        return new MessageHandlerContext(
            $client,
            $requestBody
        );
    }


    /**
     * @param RequestBuilderInterface $requestBuilder
     * @return ServerRequestInterface
     */
    abstract protected function createRequest(RequestBuilderInterface $requestBuilder): ServerRequestInterface;


    /**
     * @param ClientInterface $client
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function sendRequest(
        ClientInterface $client,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $client->sendRequest($request);
    }


    /**
     * @param ResponseInterface $response
     * @return TResponse
     */
    abstract protected function handleResponse(ResponseInterface $response): mixed;


    /**
     * @return TResponse
     */
    protected function handleError(\Throwable $t): mixed
    {
        throw $t;
    }
}
