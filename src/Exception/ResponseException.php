<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

use Gadget\Io\FormattedString;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Throwable|null $t = null
    ) {
        parent::__construct(new FormattedString(
            "Error handling response: %s %s => %s",
            $request->getMethod(),
            $request->getUri(),
            $response->getStatusCode()
        ), 0, $t);
    }
}
