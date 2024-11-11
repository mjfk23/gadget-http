<?php

declare(strict_types=1);

namespace Gadget\Http;

use Gadget\Http\Exception\HttpException;
use Gadget\Io\Cast;
use Gadget\Io\JSON;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class ApiClient extends HttpClient
{
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public static function rawResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }


    /**
     * @param ResponseInterface $response
     * @return string
     */
    public static function stringResponse(ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }


    /**
     * @param ResponseInterface $response
     * @return mixed[]
     */
    public static function jsonResponse(ResponseInterface $response): array
    {
        return Cast::toArray(JSON::decode($response->getBody()->getContents()));
    }


    /**
     * @template T
     * @param string $method
     * @param UriInterface|string $uri
     * @param (callable(ResponseInterface $response): T) $parseResponse
     * @param (callable(ServerRequestInterface $request): ServerRequestInterface)|null $formatRequest
     * @param array<string,string|string[]> $headers
     * @param mixed $body
     * @param bool $skipStatusCodeCheck
     * @return T
     */
    public function sendApiRequest(
        string $method,
        UriInterface|string $uri,
        callable $parseResponse,
        callable|null $formatRequest = null,
        array $headers = [],
        mixed $body = null,
        bool $skipStatusCodeCheck = false
    ): mixed {
        try {
            $request = $this->createApiRequest($method, $uri, $headers, $body, $formatRequest);
            $response = $this->sendRequest($request);
            return $this->parseApiResponse(
                $response,
                $parseResponse,
                $skipStatusCodeCheck
            );
        } catch (\Throwable $t) {
            throw new HttpException(
                [
                    "Error with request: %s %s",
                    $method,
                    strval($uri)
                ],
                0,
                $t
            );
        }
    }


    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string,string|string[]> $headers
     * @param mixed $body
     * @param (callable(ServerRequestInterface $request): ServerRequestInterface)|null $formatRequest
     * @return ServerRequestInterface
     */
    public function createApiRequest(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        mixed $body = null,
        callable|null $formatRequest = null
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

        return is_callable($formatRequest)
            ? $formatRequest($request)
            : $request;
    }


    /**
     * @template T
     * @param ResponseInterface $response
     * @param (callable(ResponseInterface $response): T) $parseResponse
     * @param bool $skipStatusCodeCheck
     * @return T
     */
    public function parseApiResponse(
        ResponseInterface $response,
        callable $parseResponse,
        bool $skipStatusCodeCheck = false
    ): mixed {
        return ($skipStatusCodeCheck || $response->getStatusCode() === 200)
            ? $parseResponse($response)
            : throw new HttpException(["Invalid response code: %s", $response->getStatusCode()]);
    }
}
