<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

final class AuthCode
{
    /**
     * @param string $uri
     * @param string $state
     * @param string $nonce
     * @param PKCE|null $pkce
     * @param string|null $code
     */
    public function __construct(
        public string $uri,
        public string $state,
        public string $nonce,
        public PKCE|null $pkce = null,
        public string|null $code = null
    ) {
    }
}
