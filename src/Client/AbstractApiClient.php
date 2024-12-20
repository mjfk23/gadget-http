<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Http\Message\MessageHandler;

abstract class AbstractApiClient
{
    /**
     * @param ClientInterface $client
     */
    public function __construct(private ClientInterface $client)
    {
    }


    /**
     * @template TRequest
     * @template TResponse
     * @param MessageHandler<TRequest,TResponse> $handler
     * @param TRequest|null $requestBody
     * @return TResponse
     */
    protected function invoke(
        MessageHandler $handler,
        mixed $requestBody = null
    ): mixed {
        return $this->client->handleMessage($handler, $requestBody);
    }
}
