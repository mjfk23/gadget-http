<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

final class HttpInvalidStatusCodeException extends \Exception
{
    public function __construct(int $statusCode)
    {
        parent::__construct("Invalid status code: {$statusCode}");
    }
}
