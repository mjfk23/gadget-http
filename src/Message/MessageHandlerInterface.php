<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

use Gadget\Http\Client\ClientInterface;

/**
 * @template TRequest
 * @template TResponse
 */
interface MessageHandlerInterface
{
    /**
     * @param ClientInterface $client
     * @param TRequest|null $requestBody
     * @return TResponse
     */
    public function invoke(
        ClientInterface $client,
        mixed $requestBody = null
    ): mixed;
}
