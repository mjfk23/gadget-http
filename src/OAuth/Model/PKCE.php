<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

class PKCE
{
    /**
     * @param string $mode
     * @param string $verifier
     * @param string $challenge
     */
    public function __construct(
        public string $mode,
        public string $verifier,
        public string $challenge
    ) {
    }
}
