<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

enum ContentType : string
{
    case CSS = 'text/css';
    case FORM = 'application/x-www-form-urlencoded';
    case HTML = 'text/html';
    case JAVASCRIPT = 'text/javascript';
    case JSON = 'application/json';
    case STREAM = 'application/octet-stream';
    case TEXT = 'text/plain';
    case XML = 'application/xml';
}
