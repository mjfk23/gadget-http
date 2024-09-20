<?php

declare(strict_types=1);

namespace Gadget\Http\Brightspace;

final class LoginCredentials
{
    /**
     * @param string $hostname
     * @param string $user
     * @param string $pass
     * @param int|string|null $mfa
     * @param int $orgId
     * @param string $loginToken
     * @param string[] $location
     * @param string $xsrfName
     * @param string $xsrfCode
     * @param string $hitCodeSeed
     * @param int $hitCode
     * @param string $mfaCode
     */
    public function __construct(
        public string $hostname,
        public string $user,
        public string $pass,
        public int|string|null $mfa = null,
        public int $orgId = 6606,
        public string $loginToken = '',
        public array $location = [],
        public string $xsrfName = '',
        public string $xsrfCode = '',
        public string $hitCodeSeed = '0',
        public int $hitCode = 0,
        public string $mfaCode = ''
    ) {
    }


    /**
     * @return void
     */
    public function reset(): void
    {
        $this->loginToken = '';
        $this->location = [];
        $this->xsrfName = '';
        $this->xsrfCode = '';
        $this->hitCodeSeed = '0';
        $this->hitCode = 0;
        $this->mfaCode = '';
    }
}
