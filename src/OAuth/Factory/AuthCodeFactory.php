<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Gadget\Http\ApiClient;
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
     * @return AuthCode
     */
    public function create(
        string|null $state = null,
        PKCE|null $pkce = null,
        string|null $code = null
    ): AuthCode {
        $state ??= bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        return $this->cache->set($state, new AuthCode(
            sprintf("%s?%s", $this->config->authCodeUri, ApiClient::buildQuery([
                'response_type'         => 'code',
                'client_id'             => $this->config->clientId,
                'redirect_uri'          => $this->config->redirectUri,
                'scope'                 => $this->config->scope,
                'state'                 => $state,
                'code_challenge'        => $pkce?->challenge,
                'code_challenge_method' => $pkce?->mode,
                // 'nonce'                 => $nonce,
                // 'response_mode'         => 'form_post',
                // 'prompt'                => 'select_account'
            ])),
            $state,
            $nonce,
            $pkce,
            $code
        )) ?? throw new \RuntimeException("Unable to create authorization code");
    }
}
