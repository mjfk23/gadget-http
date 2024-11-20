<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Gadget\Http\Client\Client;
use Gadget\Io\JSON;
use Gadget\Lang\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @template TResponse */
abstract class MessageHandler
{
    /** @var Client|null $client */
    private Client|null $client = null;

    /** @var RequestBuilder|null $requestBuilder */
    private RequestBuilder|null $requestBuilder = null;

    /** @var ServerRequestInterface|null $request */
    private ServerRequestInterface|null $request = null;

    /** @var ResponseInterface|null $response */
    private ResponseInterface|null $response = null;


    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        return $this->client
            ?? throw new Exception(["%s not set", Client::class]);
    }


    /**
     * @param Client $client
     * @return static
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;
        return $this;
    }


    /**
     * @return RequestBuilder
     */
    protected function getRequestBuilder(): RequestBuilder
    {
        $this->requestBuilder ??= new RequestBuilder(
            $this->getClient()->getMessageFactory(),
            $this->getClient()->getCookieJar()
        );
        return $this->requestBuilder;
    }


    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        $this->request ??= $this->createRequest();
        return $this->request;
    }


    /**
     * @return ServerRequestInterface
     */
    abstract protected function createRequest(): ServerRequestInterface;


    /**
     * @return ResponseInterface
     */
    protected function getResponse(): ResponseInterface
    {
        return $this->response
            ?? throw new Exception(["%s not set", ResponseInterface::class]);
    }


    /**
     * @param ResponseInterface $response
     * @return static
     */
    public function setResponse(ResponseInterface $response): static
    {
        $this->response = $response;
        return $this;
    }


    /**
     * @return TResponse
     */
    abstract public function handleResponse(): mixed;


    /**
     * @param \Throwable $t
     * @return TResponse
     */
    public function handleError(\Throwable $t): mixed
    {
        throw $t;
    }


    /**
     * @return mixed
     */
    protected function decodeResponse(): mixed
    {
        return JSON::decode($this->getResponse()->getBody()->getContents());
    }
}
