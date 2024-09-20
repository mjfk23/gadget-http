<?php

declare(strict_types=1);

namespace Gadget\Http\Exception;

final class OAuthInvalidNonceException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Invalid nonce");
    }
}
