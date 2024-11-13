<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

use Gadget\Lang\Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;

class HttpClientException extends Exception implements ClientExceptionInterface
{
    /**
     * @param RequestInterface $request
     * @param \Throwable $previous
     * @param int $code
     */
    public function __construct(
        public RequestInterface $request,
        \Throwable $previous,
        int $code = 0
    ) {
        parent::__construct(
            [
                "Error with request: %s %s",
                $request->getMethod(),
                $request->getUri()
            ],
            $code,
            $previous
        );
    }
}
