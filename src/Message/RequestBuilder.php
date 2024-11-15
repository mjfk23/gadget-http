<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Gadget\Io\JSON;
use Psr\Http\Message\UriInterface;

class RequestBuilder
{
    /** @var RequestMethod $method */
    private RequestMethod $method = RequestMethod::GET;

    /** @var UriInterface|string $uri */
    private UriInterface|string $uri = '';

    /** @var array<string,int|float|string|bool> $queryParams */
    private array $queryParams = [];

    /** @var array<string,string|string[]> $headers */
    private array $headers = [];

    /** @var array<string,string> $cookies */
    private array $cookies = [];

    /** @var array<string,mixed> $attributes */
    private array $attributes = [];

    /** @var array{mixed,ContentType}|null $body */
    private array|null $body = null;


    /** @return RequestMethod */
    public function getMethod(): RequestMethod
    {
        return $this->method;
    }


    /**
     * @param RequestMethod $method
     * @return static
     */
    public function setMethod(RequestMethod $method): static
    {
        $this->method = $method;
        return $this;
    }


    /** @return UriInterface|string */
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


    /** @return array<string,int|float|string|bool> */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }


    /**
     * @param array<string,int|float|string|bool|null> $queryParams
     * @return static
     */
    public function setQueryParams(array $queryParams): static
    {
        $this->queryParams = array_filter($queryParams, is_scalar(...));
        return $this;
    }


    /**
     * @param string $name
     * @return int|float|string|bool|null
     */
    public function getQueryParam(string $name): int|float|string|bool|null
    {
        return $this->queryParams[$name] ?? null;
    }


    /**
     * @param string $name
     * @param int|float|string|bool|null $value
     * @return static
     */
    public function setQueryParam(
        string $name,
        int|float|string|bool|null $value
    ): static {
        if (is_scalar($value)) {
            $this->queryParams[$name] = $value;
        } else {
            unset($this->queryParams[$name]);
        }
        return $this;
    }


    /** @return array<string,string|string[]> */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * @param array<string,string|string[]|null> $headers
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = array_filter($headers);
        return $this;
    }


    /**
     * @param string $name
     * @return string|string[]|null
     */
    public function getHeader(string $name): string|array|null
    {
        $value = $this->headers[$name] ?? null;
        return is_array($value)
            ? match (count($value)) {
                0, 1 => array_shift($value),
                default => $value
            }
            : $value;
    }


    /**
     * @param string $name
     * @return string
     */
    public function getSingleHeader(string $name): string|null
    {
        $value = $this->headers[$name] ?? null;
        return is_array($value) ? array_shift($value) : $value;
    }


    /**
     * @param string $name
     * @param int|float|string|bool|string[]|null $value
     * @return static
     */
    public function setHeader(
        string $name,
        int|float|string|bool|array|null $value
    ): static {
        if ($value !== null) {
            $this->headers[$name] = is_array($value) || is_string($value)
                ? $value
                : strval($value);
        } else {
            unset($this->headers[$name]);
        }
        return $this;
    }


    /** @return array<string,string> */
    public function getCookies(): array
    {
        return $this->cookies;
    }


    /**
     * @param array<string,string|null> $cookies
     * @return static
     */
    public function setCookies(array $cookies): static
    {
        $this->cookies = array_filter($cookies);
        return $this;
    }


    /**
     * @param string $name
     * @return string
     */
    public function getCookie(string $name): string|null
    {
        return $this->cookies[$name] ?? null;
    }


    /**
     * @param string $name
     * @param string|null $value
     * @return static
     */
    public function setCookie(
        string $name,
        string|null $value
    ): static {
        if (is_string($value)) {
            $this->cookies[$name] = $value;
        } else {
            unset($this->cookies[$name]);
        }
        return $this;
    }


    /** @return array<string,mixed> */
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
        $this->attributes = array_filter($attributes);
        return $this;
    }


    /**
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }


    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setAttribute(
        string $name,
        mixed $value
    ): static {
        if ($value !== null) {
            $this->attributes[$name] = $value;
        } else {
            unset($this->attributes);
        }
        return $this;
    }


    /** @return bool */
    public function allowBody(): bool
    {
        return in_array($this->getMethod(), [RequestMethod::PUT, RequestMethod::POST], true);
    }


    /** @return ContentType|null */
    public function getContentType(): ContentType|null
    {
        return ($this->body[1] ?? null);
    }


    /** @return mixed */
    public function getBody(): mixed
    {
        return ($this->body[0] ?? null);
    }


    /**
     * @param mixed $body
     * @param ContentType|null $contentType
     * @return static
     */
    public function setBody(
        mixed $body,
        ContentType|null $contentType
    ): static {
        $this->body = ($body !== null && $contentType !== null) ? [$body, $contentType] : null;
        return $this;
    }


    /**
     * @return string
     */
    public function serializeUri(): string
    {
        return rtrim(sprintf(
            '%s?%s',
            $this->getUri(),
            $this->serializeQuery()
        ), '?');
    }


    /**
     * @return string
     */
    public function serializeQuery(): string
    {
        return http_build_query(
            $this->getQueryParams(),
            '',
            null,
            PHP_QUERY_RFC3986
        );
    }


    /**
     * @return string|null
     */
    public function serializeBody(): string|null
    {
        $body = $this->getBody();
        $contentType = $this->getContentType();

        if ($body === null || $contentType === null) {
            return null;
        }

        return match ($contentType) {
            ContentType::FORM => match (true) {
                is_object($body) || is_array($body) => http_build_query(
                    $body,
                    '',
                    null,
                    PHP_QUERY_RFC3986
                ),
                is_string($body) => $body,
                default => null
            },
            ContentType::JSON => JSON::encode($body),
            ContentType::TEXT => match (true) {
                is_scalar($body) => strval($body),
                is_object($body) && $body instanceof \Stringable => $body->__toString(),
                default => null
            },
            default => null
        };
    }
}
