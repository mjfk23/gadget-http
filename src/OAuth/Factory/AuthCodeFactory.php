<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Gadget\Http\Client\ApiCaller;
use Gadget\Http\OAuth\Cache\AuthCodeCache;
use Gadget\Http\OAuth\Model\AuthCode;
use Gadget\Http\OAuth\Model\Config;
use Gadget\Http\OAuth\Model\PKCE;

class AuthCodeFactory
{
    /**
     * @param Config $config
     * @param AuthCodeCache $cache
     */
    public function __construct(
        protected Config $config,
        protected AuthCodeCache $cache
    ) {
    }


    /**
     * @param 'code'|'id_token' $responseType
     * @param string|null $state
     * @param PKCE|null $pkce
     * @param string|null $code
     * @return AuthCode
     */
    public function create(
        string $responseType = 'code',
        string|null $state = null,
        PKCE|null $pkce = null,
        string|null $code = null
    ): AuthCode {
        $state ??= bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        $params = [
            'response_type'         => $responseType,
            'client_id'             => $this->config->clientId,
            'redirect_uri'          => $this->config->redirectUri,
            'scope'                 => $this->config->scope,
            'state'                 => $state,
            'code_challenge'        => $pkce?->challenge,
            'code_challenge_method' => $pkce?->mode
        ];
        if ($responseType === 'id_token') {
            $params += [
                'nonce' => $nonce,
                'response_mode' => 'form_post',
                'prompt' => 'select_account'
            ];
        }

        return $this->cache->set($state, new AuthCode(
            sprintf("%s?%s", $this->config->authCodeUri, ApiCaller::buildQuery($params)),
            $state,
            $nonce,
            $pkce,
            $code
        )) ?? throw new \RuntimeException("Unable to create authorization code");
    }
}
