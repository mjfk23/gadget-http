<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Io\Cast;
use Gadget\Io\JSON;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @template TRequest
 * @template TResponse
 */
class ApiCaller
{
    /**
     * @template T
     * @param ResponseInterface $response
     * @param (callable(mixed $values): T) $createResponse
     * @return T
     */
    public static function createResponseFromJson(
        ResponseInterface $response,
        callable $createResponse
    ): mixed {
        return $createResponse(JSON::decode($response->getBody()->getContents()));
    }


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


    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string,string|string[]> $headers
     * @param array<string,scalar|null>|string $query
     * @param TRequest|null $body
     * @param array<string,mixed> $attributes
     * @param (callable(ResponseInterface $response):TResponse)|null $createResponse
     */
    public function __construct(
        private string $method = '',
        private UriInterface|string $uri = '',
        private array $headers = [],
        private array|string $query = [],
        private mixed $body = null,
        private array $attributes = [],
        private mixed $createResponse = null
    ) {
    }


    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }


    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface|string
    {
        return $this->uri;
    }


    /**
     * @param UriInterface|string $uri
     * @return static
     */
    public function setUri(UriInterface|string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }


    /**
     * @return array<string,string|string[]>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * @param array<string,string|string[]> $headers
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }


    /**
     * @param string $name
     * @param string|string[]|null $value
     * @return static
     */
    public function setHeader(
        string $name,
        string|array|null $value
    ): static {
        if ($value !== null) {
            $this->headers[$name] = $value;
        } else {
            unset($this->headers[$name]);
        }
        return $this;
    }


    /**
     * @return string
     */
    public function getQuery(): string|null
    {
        $query = is_array($this->query) ? self::buildQuery($this->query) : $this->query;
        return strlen($query) > 0 ? $query : null;
    }


    /**
     * @param array<string,scalar|null>|string $query
     * @return static
     */
    public function setQuery(array|string $query): static
    {
        $this->query = $query;
        return $this;
    }


    /**
     * @param string $name
     * @param scalar|null $value
     * @return static
     */
    public function setQueryParam(
        string $name,
        mixed $value
    ): static {
        if (!is_array($this->query)) {
            $this->query = [];
        }
        $this->query[$name] = $value;
        return $this;
    }


    /**
     * @return TRequest|null
     */
    public function getBody(): mixed
    {

        return $this->body;
    }


    /**
     * @param TRequest|null $body
     * @return static
     */
    public function setBody(mixed $body): static
    {
        $this->body = $body;
        return $this;
    }


    /**
     * @return array<string,mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }


    /**
     * @param array<string,mixed> $attributes
     * @return static
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }


    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setAttribute(string $name, mixed $value): static
    {
        if ($value !== null) {
            $this->attributes[$name] = $value;
        } else {
            unset($this->attributes[$name]);
        }
        return $this;
    }


    /**
     * @param ServerRequestFactoryInterface $requestFactory
     * @return ServerRequestInterface
     */
    public function createRequest(ServerRequestFactoryInterface $requestFactory): ServerRequestInterface
    {
        $request = $requestFactory->createServerRequest(
            $this->getMethod(),
            $this->getUri()
        );

        $headers = $this->getHeaders();
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $contentType = $request->getHeader('Content-Type')[0] ?? null;

        /** @var mixed $body */
        $body = $this->getBody();
        $bodyContents = Cast::toValueOrNull(match ($contentType) {
            'application/x-www-form-urlencoded' => is_array($body) ? self::buildQuery($body) : $body,
            'application/json' => !is_string($body) ? JSON::encode($body) : $body,
            default => $body
        }, Cast::toString(...));
        if ($bodyContents !== null) {
            $request->getBody()->write($bodyContents);
        }

        $query = $this->getQuery();
        if ($query !== null) {
            $request->withUri($request->getUri()->withQuery($query));
        }

        $attributes = $this->getAttributes();
        foreach ($attributes as $name => $attribute) {
            $request = $request->withAttribute($name, $attribute);
        }

        return $request;
    }


    /**
     * @param ResponseInterface $response
     * @return TResponse
     */
    public function createResponse(ResponseInterface $response): mixed
    {
        return is_callable($this->createResponse)
            ? ($this->createResponse)($response)
            : throw new \RuntimeException();
    }
}
