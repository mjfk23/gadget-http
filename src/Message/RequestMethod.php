<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

enum RequestMethod : string
{
    case GET = 'GET';
    case PUT = 'PUT';
    case POST = 'POST';
    case DELETE = 'DELETE';
}
