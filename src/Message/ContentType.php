<?php

declare(strict_types=1);

namespace Gadget\Http\Message;

enum ContentType : string
{
    case GZIP = 'application/gzip';
    case JSON = 'application/json';
    case STREAM = 'application/octet-stream';
    case PDF = 'application/pdf';
    case DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    case FORM = 'application/x-www-form-urlencoded';
    case XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    case XML = 'application/xml';
    case ZIP = 'application/zip';

    case CSS = 'text/css';
    case CSV = 'text/csv';
    case HTML = 'text/html';
    case JAVASCRIPT = 'text/javascript';
    case TEXT = 'text/plain';
}
