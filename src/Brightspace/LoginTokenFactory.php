<?php

declare(strict_types=1);

namespace Gadget\Http\Brightspace;

use Gadget\Http\ApiClient;
use Gadget\Security\MFA\TOTP;
use Psr\Http\Message\ResponseInterface;

final class LoginTokenFactory
{
    public const LOGIN_URI         = '/d2l/lp/auth/login/login.d2l';
    public const MFA_URI           = '/d2l/lp/auth/twofactorauthentication/TwoFactorCodeEntry.d2l';
    public const PROCESS_LOGIN_URI = '/d2l/lp/auth/login/ProcessLoginActions.d2l';
    public const HOME_URI          = '/d2l/home';


    /**
     * @param ApiClient $apiClient
     */
    public function __construct(private ApiClient $apiClient)
    {
    }


    /**
     * @param LoginCredentials $credentials
     * @return string
     */
    public function create(LoginCredentials $credentials): string
    {
        try {
            return $this
                ->submitCredentials($credentials)
                ->processMFA($credentials)
                ->getLoginToken($credentials);
        } finally {
            $credentials->reset();
        }
    }


    /**
     * @param LoginCredentials $credentials
     * @return self
     */
    private function submitCredentials(LoginCredentials $credentials): self
    {
        return $this->getTokenFromResponse(
            $credentials,
            $this->apiClient->sendRequest(
                $this->apiClient->createApiRequest(
                    method: 'POST',
                    uri: sprintf("https://%s%s", $credentials->hostname, self::LOGIN_URI),
                    headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
                    body: [
                        'd2l_referrer' => '',
                        'noredirect'   => '1',
                        'loginPath'    => self::LOGIN_URI,
                        'userName'     => $credentials->user,
                        'password'     => $credentials->pass
                    ]
                )
            )
        );
    }


    /**
     * @param LoginCredentials $credentials
     * @return self
     */
    private function processMFA(LoginCredentials $credentials): self
    {
        return ($credentials->mfa !== null && in_array(self::MFA_URI, $credentials->location, true))
            ? $this
                ->generateMFA($credentials)
                ->submitMFA($credentials)
                ->processLoginActions($credentials)
            : $this;
    }


    /**
     * @param LoginCredentials $credentials
     * @return string
     */
    private function getLoginToken(LoginCredentials $credentials): string
    {
        return in_array(self::HOME_URI, $credentials->location, true)
            ? $credentials->loginToken
            : throw new \RuntimeException("Error logging in");
    }


    /**
     * @param LoginCredentials $credentials
     * @return self
     */
    private function generateMFA(LoginCredentials $credentials): self
    {
        list(
            $credentials->xsrfName,
            $credentials->xsrfCode,
            $credentials->hitCodeSeed
        ) = $this->parseMFA(
            $this->apiClient->sendRequest($this->apiClient->createApiRequest(
                method: 'GET',
                uri: sprintf("https://%s%s", $credentials->hostname, self::MFA_URI),
                headers: ['Cookie' => $credentials->loginToken]
            ))
        );

        $rightNow = time();
        $credentials->hitCode = intval($credentials->hitCodeSeed) + ((1000 * $rightNow + 100000000) % 100000000);
        $credentials->mfaCode = match (true) {
            is_string($credentials->mfa) => (new TOTP())
                ->setKey($credentials->mfa)
                ->setCurrentTime($rightNow)
                ->generate(),
            is_int($credentials->mfa) => strval($credentials->mfa),
            default => ''
        };

        return $this;
    }


    /**
     * @param LoginCredentials $credentials
     * @return self
     */
    private function submitMFA(LoginCredentials $credentials): self
    {
        return $this->getTokenFromResponse(
            $credentials,
            $this->apiClient->sendRequest($this->apiClient->createApiRequest(
                method: 'POST',
                uri: sprintf(
                    "https://%s%s%s",
                    $credentials->hostname,
                    self::MFA_URI,
                    "?ou={$credentials->orgId}&d2l_rh=rpc&d2l_rt=call"
                ),
                headers: [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cookie' => $credentials->loginToken
                ],
                body: [
                    'd2l_rf' => 'VerifyPin',
                    'params' => '{"param1":"' . $credentials->mfaCode . '"}',
                    "{$credentials->xsrfName}" => $credentials->xsrfCode,
                    'd2l_hitcode' => $credentials->hitCode,
                    'd2l_action' => 'rpc'
                ]
            ))
        );
    }


    /**
     * @param LoginCredentials $credentials
     * @return self
     */
    private function processLoginActions(LoginCredentials $credentials): self
    {
        return $this->getTokenFromResponse(
            $credentials,
            $this->apiClient->sendRequest($this->apiClient->createApiRequest(
                method: 'GET',
                uri: sprintf("https://%s%s", $credentials->hostname, self::PROCESS_LOGIN_URI),
                headers: ['Cookie' => $credentials->loginToken]
            ))
        );
    }


    /**
     * @param ResponseInterface $response
     * @return array{string,string,string}
     */
    private function parseMFA(ResponseInterface $response): array
    {
        /**
         * @param string[]|false $grep
         * @return string
         */
        $subject = fn (array|false $grep): string => is_array($grep) ? (string)(end($grep) ?? "") : "";

        $matches = [];
        preg_match(
            '/\\\"P\\\"\:\[(.*)\]/',
            $subject(preg_grep(
                '/.*D2L\.LP\.Web\.Authentication\.Xsrf\.Init/',
                explode("\n", $response->getBody()->getContents())
            )),
            $matches
        );

        /** @var array{string,string,string} */
        return array_slice([
            ...array_map(
                fn(string $v) => trim($v, '\"'),
                explode(",", $matches[1] ?? ",,")
            ),
            '',
            '',
            '0'
        ], 0, 3);
    }


    /**
     * @param LoginCredentials $credentials
     * @param ResponseInterface $response
     * @return self
     */
    private function getTokenFromResponse(
        LoginCredentials $credentials,
        ResponseInterface $response
    ): self {
        $credentials->loginToken = implode("; ", array_map(
            fn($v) => trim(explode(";", $v)[0] ?? ''),
            $response->getHeader("Set-Cookie")
        ));

        $credentials->location = $response->getHeader('Location');

        return $this;
    }
}
