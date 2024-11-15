<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

use Gadget\Lang\Exception;
use Psr\Http\Client\ClientExceptionInterface;

class ClientException extends Exception implements ClientExceptionInterface
{
}
