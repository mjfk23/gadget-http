<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Http\Exception\HttpClientException;
use Gadget\Http\Exception\HttpException;
use Gadget\Http\Middleware\MiddlewareStack;
use Gadget\Util\Stack;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpClient implements
    ClientInterface,
    RequestHandlerInterface,
    RequestFactoryInterface,
    ServerRequestFactoryInterface
{
    /**
     * @param mixed[] $params
     * @return string
     */
    public static function buildQuery(array $params): string
    {
        return http_build_query(
            $params,
            '',
            null,
            PHP_QUERY_RFC3986
        );
    }


    /** @var Stack<MiddlewareStack> $callStack */
    private Stack $callStack;


    /**
     * @param ClientInterface $client
     * @param ServerRequestFactoryInterface $factory
     * @param MiddlewareStack|null $middlewareStack
     */
    public function __construct(
        private ClientInterface $client,
        private ServerRequestFactoryInterface $factory,
        private MiddlewareStack|null $middlewareStack = null
    ) {
        /** @var Stack<MiddlewareStack> $callStack */
        $callStack = new Stack();
        $this->callStack = $callStack;
    }


    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $this->callStack->push(new MiddlewareStack($this->getMiddlewareStack()->getElements()));
            return $this->handle($this->getServerRequest($request));
        } catch (\Throwable $t) {
            throw new HttpClientException($request, $t);
        } finally {
            $this->callStack->pop();
        }
    }


    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->callStack->peek()?->pop()?->process($request, $this)
                ?? $this->client->sendRequest($request);
        } catch (\Throwable $t) {
            throw new HttpClientException($request, $t);
        }
    }


    /**
     * Create a new request.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If the value is a string, the factory MUST
     * create a UriInterface instance based on it.
     * @return RequestInterface
     */
    public function createRequest(
        string $method,
        mixed $uri
    ): RequestInterface {
        return $this->createServerRequest($method, $uri);
    }


    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing of the given values is performed,
     * and, in particular, no attempt is made to determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If the value is a string, the factory MUST
     * create a UriInterface instance based on it.
     * @param mixed[] $serverParams Array of SAPI parameters with which to seed the generated request instance.
     * @return ServerRequestInterface
     */
    public function createServerRequest(
        string $method,
        mixed $uri,
        array $serverParams = []
    ): ServerRequestInterface {
        return $this->factory->createServerRequest($method, $uri, $serverParams);
    }


    /**
     * Converts a RequestInterface to a ServerRequestInterface.
     *
     * @param RequestInterface $request
     * @return ServerRequestInterface
     */
    protected function getServerRequest(RequestInterface $request): ServerRequestInterface
    {
        if ($request instanceof ServerRequestInterface) {
            return $request;
        }

        $serverRequest = $this
            ->createServerRequest($request->getMethod(), $request->getUri())
            ->withProtocolVersion($request->getProtocolVersion())
            ->withRequestTarget($request->getRequestTarget())
            ->withBody($request->getBody());

        /** @var array<string,string|string[]> $headers */
        $headers = $request->getHeaders();
        foreach ($headers as $name => $value) {
            $serverRequest = $serverRequest->withHeader($name, $value);
        }

        return $serverRequest;
    }


    /**
     * @return Stack<MiddlewareInterface>
     */
    public function getMiddlewareStack(): Stack
    {
        $this->middlewareStack ??= new MiddlewareStack();
        return $this->middlewareStack;
    }


    /**
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function addMiddleware(...$middleware): void
    {
        $this->getMiddlewareStack()->push(...$middleware);
    }
}
