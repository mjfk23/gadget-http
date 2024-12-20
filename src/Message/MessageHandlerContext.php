<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Gadget\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template TRequest
 */
class MessageHandlerContext
{
    private ServerRequestInterface|null $request = null;
    private ResponseInterface|null $response = null;

    /**
     * @param ClientInterface $client
     * @param TRequest|null $requestBody
     */
    public function __construct(
        private ClientInterface $client,
        private mixed $requestBody
    ) {
    }


    /**
     * @return ClientInterface
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }


    /**
     * @param ClientInterface $client
     * @return static
     */
    public function setClient(ClientInterface $client): static
    {
        $this->client = $client;
        return $this;
    }


    /**
     * @return bool
     */
    public function hasRequest(): bool
    {
        return $this->request !== null;
    }


    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request ?? throw new \RuntimeException();
    }


    /**
     * @param ServerRequestInterface|null $request
     * @return static
     */
    public function setRequest(ServerRequestInterface|null $request): static
    {
        $this->request = $request;
        return $this;
    }


    /**
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }


    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response ?? throw new \RuntimeException();
    }


    /**
     * @param ResponseInterface|null $response
     * @return static
     */
    public function setResponse(ResponseInterface|null $response): static
    {
        $this->response = $response;
        return $this;
    }


    /**
     * @return bool
     */
    public function hasRequestBody(): bool
    {
        return $this->requestBody !== null;
    }


    /**
     * @return TRequest
     */
    public function getRequestBody(): mixed
    {
        return $this->requestBody ?? throw new \ValueError();
    }


    /**
     * @param TRequest|null $requestBody
     * @return static
     */
    public function setRequestBody(mixed $requestBody): static
    {
        $this->requestBody = $requestBody;
        return $this;
    }
}
