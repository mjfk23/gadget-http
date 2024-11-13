<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class ApiClient extends HttpClient
{
    /**
     * @template TRequest
     * @template TResponse
     * @param ApiCaller<TRequest,TResponse> $apiCaller
     * @return TResponse
     */
    public function sendApiRequest(ApiCaller $apiCaller): mixed
    {
        $request = $apiCaller->createRequest($this);
        $response = $this->sendRequest($request);
        return $apiCaller->createResponse($response);
    }


    /**
     * @template TRequest
     * @template TResponse
     * @param string $method
     * @param UriInterface|string $uri
     * @param array<string,string|string[]> $headers
     * @param array<string,scalar|null>|string $query
     * @param TRequest|null $body
     * @param array<string,mixed> $attributes
     * @param int[] $validStatusCodes
     * @param (callable(mixed $response):TResponse)|null $createResponse
     * @return ApiCaller<TRequest,TResponse> $apiCaller
     */
    public function createApiCaller(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        array|string $query = [],
        mixed $body = null,
        array $attributes = [],
        array $validStatusCodes = [200],
        callable|null $createResponse = null,
    ): ApiCaller {
        return new ApiCaller(
            $method,
            $uri,
            $headers,
            $query,
            $body,
            $attributes,
            ($createResponse !== null)
                ? fn(ResponseInterface $response) => in_array($response->getStatusCode(), $validStatusCodes, true)
                    ? ApiCaller::createResponseFromJson($response, $createResponse)
                    : throw new \RuntimeException()
                : null
        );
    }
}
