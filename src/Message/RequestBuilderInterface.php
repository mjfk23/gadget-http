<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

interface RequestBuilderInterface
{
    /**
     * @return static
     */
    public function clone(): static;


    /**
     * @return static
     */
    public function reset(): static;


    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface;


    /**
     * @return string
     */
    public function getMethod(): string;


    /**
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): static;


    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface;


    /**
     * @param UriInterface|string $uri
     * @return static
     */
    public function setUri(UriInterface|string $uri): static;


    /**
     * @return string
     */
    public function getScheme(): string;


    /**
     * @param string $scheme
     * @return static
     */
    public function setScheme(string $scheme): static;


    /**
     * @return string
     */
    public function getHost(): string;


    /**
     * @param string $host
     * @return static
     */
    public function setHost(string $host): static;


    /**
     * @return string
     */
    public function getPath(): string;


    /**
     * @param string $path
     * @return static
     */
    public function setPath(string $path): static;


    /**
     * @return array<string,int|float|string|bool|null>
     */
    public function getQueryParams(): array;


    /**
     * @param array<string,int|float|string|bool|null> $queryParams
     * @return static
     */
    public function setQueryParams(array $queryParams): static;


    /**
     * @return string
     */
    public function getBody(): string;


    /**
     * @param string $contentType
     * @param mixed $body
     * @return static
     */
    public function setBody(
        string $contentType,
        mixed $body
    ): static;


    /**
     * @param string $header
     * @return string[]
     */
    public function getHeader(string $header): array;


    /**
     * @param string $header
     * @return string|null
     */
    public function getSingleHeader(string $header): string|null;


    /**
     * @param string $name
     * @param string|string[] $value
     * @return static
     */
    public function setHeader(
        string $name,
        string|array $value
    ): static;
}
