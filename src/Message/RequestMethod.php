<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

enum RequestMethod : string
{
    case GET = 'GET';
    case HEAD = 'HEAD';
    case PUT = 'PUT';
    case POST = 'POST';
    case DELETE = 'DELETE';
    case CONNECT = 'CONNECT';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case PATCH = 'PATCH';
}
