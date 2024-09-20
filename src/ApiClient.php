<?php

declare(strict_types=1);

namespace Gadget\Http;

use Gadget\Http\Exception\HttpException;
use Gadget\Io\Cast;
use Gadget\Io\JSON;
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

class ApiClient implements
    RequestHandlerInterface,
    RequestFactoryInterface,
    ServerRequestFactoryInterface,
    ClientInterface
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


    /** @var Stack<MiddlewareInterface> $middlewareStack */
    private Stack $middlewareStack;

    /** @var Stack<Stack<MiddlewareInterface>> $callStack */
    private Stack $callStack;


    /**
     * @param ClientInterface $client
     * @param ServerRequestFactoryInterface $factory
     * @param Stack<MiddlewareInterface>|null $middlewareStack
     */
    public function __construct(
        private ClientInterface $client,
        private ServerRequestFactoryInterface $factory,
        Stack|null $middlewareStack = null
    ) {
        $this->middlewareStack = $middlewareStack instanceof Stack ? $middlewareStack : new Stack();
        $this->callStack = new Stack();
    }


    /** @inheritdoc */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $this->callStack->push(new Stack($this->middlewareStack->toArray()));
            return $this->handle($this->getServerRequest($request));
        } finally {
            $this->callStack->pop();
        }
    }


    /** @inheritdoc */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->callStack->empty()) {
            return $this->sendRequest($request);
        }

        $middlewareStack = $this->callStack->peek();
        $middleware = $middlewareStack?->pop();

        try {
            return ($middleware !== null)
                ? $middleware->process($request, $this)
                : $this->client->sendRequest($request);
        } finally {
            if ($middlewareStack !== null && $middleware !== null) {
                $middlewareStack->push($middleware);
            }
        }
    }


    /** @inheritdoc */
    public function createRequest(
        string $method,
        mixed $uri
    ): RequestInterface {
        return $this->createServerRequest($method, $uri);
    }


    /**
     * Create a new server request.
     *
     * Note that server-params are taken precisely as given - no parsing/processing
     * of the given values is performed, and, in particular, no attempt is made to
     * determine the HTTP method or URI, which must be provided explicitly.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     * @param mixed[] $serverParams Array of SAPI parameters with which to seed
     *     the generated request instance.
     *
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
        return $this->middlewareStack;
    }


    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string,string|string[]> $headers
     * @param mixed $body
     * @param bool $skipStatusCodeCheck
     * @return mixed[]
     */
    public function sendApiRequest(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null,
        bool $skipStatusCodeCheck = false
    ): array {
        return $this->parseJsonResponse(
            $this->sendRequest(
                $this->createApiRequest(
                    $method,
                    $uri,
                    $headers,
                    $body
                )
            ),
            $skipStatusCodeCheck
        );
    }


    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string,string|string[]> $headers
     * @param mixed $body
     * @return ServerRequestInterface
     */
    public function createApiRequest(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null
    ): ServerRequestInterface {
        $request = $this->createServerRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $contentType = $request->getHeader('Content-Type')[0] ?? null;
            $request->getBody()->write(Cast::toString(match ($contentType) {
                'application/x-www-form-urlencoded' => is_array($body) ? ApiClient::buildQuery($body) : $body,
                'application/json' => !is_string($body) ? JSON::encode($body) : $body,
                default => $body
            }));
        }

        return $request;
    }


    /**
     * @param ResponseInterface $response
     * @param bool $skipStatusCodeCheck
     * @return mixed[]
     */
    public function parseJsonResponse(
        ResponseInterface $response,
        bool $skipStatusCodeCheck = false
    ): array {
        return ($skipStatusCodeCheck || $response->getStatusCode() === 200)
            ? Cast::toArray(JSON::decode($response->getBody()->getContents()))
            : throw new HttpException(["Invalid response code: %s", $response->getStatusCode()]);
    }
}
