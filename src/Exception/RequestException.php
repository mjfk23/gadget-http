<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

class RequestException extends HttpException
{
    public function __construct(\Throwable|null $t = null)
    {
        parent::__construct("Error building request", 0, $t);
    }
}
