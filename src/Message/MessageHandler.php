<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/** @template TResponse */
abstract class MessageHandler
{
    /** @var RequestBuilder|null $requestBuilder */
    private RequestBuilder|null $requestBuilder = null;

    /** @var ServerRequestInterface|null $request */
    private ServerRequestInterface|null $request = null;

    /** @var ResponseInterface|null $response */
    private ResponseInterface|null $response = null;


    /** @return ServerRequestInterface */
    public function createRequest(ServerRequestFactoryInterface $requestFactory): ServerRequestInterface
    {
        $requestBuilder = $this->getRequestBuilder();

        $request = $requestFactory->createServerRequest(
            $requestBuilder->getMethod()->value,
            $requestBuilder->getUri()
        );

        $request = $request->withUri(
            $request->getUri()
                ->withQuery($this->serializeQuery())
        );

        if ($requestBuilder->allowBody()) {
            $contentType = $requestBuilder->getContentType();
            $body = $this->serializeBody();

            if ($contentType !== null && $body !== null) {
                $requestBuilder
                    ->setHeader('Content-Type', $contentType->value)
                    ->setHeader('Content-Length', strlen($body));
                $request->getBody()->write($body);
            }
        }

        $headers = $requestBuilder->getHeaders();
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (!$request->hasHeader('Cookie')) {
            $request = $request->withCookieParams($requestBuilder->getCookies());
        }

        $attributes = $requestBuilder->getAttributes();
        foreach ($attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        return $request;
    }


    /**
     * @param ResponseInterface $response
     * @return TResponse
     */
    abstract public function handleResponse(ResponseInterface $response): mixed;


    /**
     * @param \Throwable $t
     * @return TResponse
     */
    public function handleError(\Throwable $t): mixed
    {
        throw $t;
    }


    /** @return RequestBuilder */
    protected function getRequestBuilder(): RequestBuilder
    {
        $this->requestBuilder ??= $this->createRequestBuilder();
        return $this->requestBuilder;
    }


    /**
     * @return string
     */
    protected function serializeQuery(): string
    {
        return $this->getRequestBuilder()->serializeQuery();
    }


    /**
     * @return string|null
     */
    protected function serializeBody(): string|null
    {
        return $this->getRequestBuilder()->serializeBody();
    }


    /** @return ServerRequestInterface|null */
    protected function getRequest(): ServerRequestInterface|null
    {
        return $this->request;
    }


    /** @return RequestBuilder */
    protected function createRequestBuilder(): RequestBuilder
    {
        return new RequestBuilder();
    }


    /**
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    public function setRequest(ServerRequestInterface $request): ServerRequestInterface
    {
        $this->request = $request;
        return $this->request;
    }


    /** @return ResponseInterface|null */
    protected function getResponse(): ResponseInterface|null
    {
        return $this->response;
    }


    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function setResponse(ResponseInterface $response): ResponseInterface
    {
        $this->response = $response;
        return $this->response;
    }
}
