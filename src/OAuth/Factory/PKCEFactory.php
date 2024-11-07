<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Gadget\Http\OAuth\Model\PKCE;

class PKCEFactory
{
    /** @var string */
    protected const PKCE_VERIFIER_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';


    /**
     * @param string $challengeMode
     * @param string $hashAlgo
     */
    public function __construct(
        private string $challengeMode = 'S256',
        private string $hashAlgo = 'SHA256'
    ) {
    }


    /**
     * @return PKCE
     */
    public function create(): PKCE
    {
        $verifier = join(
            array_map(
                fn (int $v): string => substr(
                    static::PKCE_VERIFIER_CHARS,
                    $v % strlen(static::PKCE_VERIFIER_CHARS),
                    1
                ),
                $this->getRandomBytes()
            )
        );

        return new PKCE(
            $this->challengeMode,
            $verifier,
            base64_encode(hash($this->hashAlgo, $verifier, true)),
        );
    }


    /**
     * @return int[]
     */
    private function getRandomBytes(): array
    {
        $size = random_int(43, 128);

        /** @var int[]|false $bytes */
        $bytes = unpack("C{$size}", random_bytes($size));
        return is_array($bytes)
            ? $bytes
            : throw new \Random\RandomError("Unable to generate random number to use in PKCE verifier");
    }
}
