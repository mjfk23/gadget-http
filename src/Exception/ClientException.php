<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

use Gadget\Io\FormattedString;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

class ClientException extends HttpException implements ClientExceptionInterface
{
    public function __construct(
        ServerRequestInterface $request,
        \Throwable|null $t = null
    ) {
        parent::__construct(new FormattedString(
            "Error sending request: %s %s",
            $request->getMethod(),
            $request->getUri()
        ), 0, $t);
    }
}
