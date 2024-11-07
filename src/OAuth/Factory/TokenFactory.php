<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Firebase\JWT\JWT;
use Gadget\Cache\CacheItemPool;
use Gadget\Http\Exception\OAuthException;
use Gadget\Http\ApiClient;
use Gadget\Http\HttpClient;
use Gadget\Http\OAuth\Cache\CachedKeySet;
use Gadget\Http\OAuth\Model\Config;
use Gadget\Http\OAuth\Model\IdToken;
use Gadget\Http\OAuth\Model\PKCE;
use Gadget\Http\OAuth\Model\Token;
use Gadget\Io\Cast;
use Psr\Http\Message\ResponseInterface;

class TokenFactory
{
    /**
     * @param Config $config
     * @param ApiClient $apiClient
     * @param CachedKeySet $jwks
     * @param CacheItemPool $cache
     */
    public function __construct(
        protected Config $config,
        protected ApiClient $apiClient,
        protected CachedKeySet $jwks,
        protected CacheItemPool $cache,
    ) {
        $this->cache = $cache->withNamespace(self::class);
    }


    /**
     * @param string|null $state
     * @param PKCE|null $pkce
     * @return array{string,string,string}
     */
    public function createAuthCodeUri(
        string|null $state = null,
        PKCE|null $pkce = null
    ): array {
        list($state, $nonce) = $this->generateStateNonce($state);
        return [
            sprintf("%s?%s", $this->config->authCodeUri, HttpClient::buildQuery([
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
            $nonce
        ];
    }


    /**
     * @param string $state
     * @param string $authCode
     * @param PKCE|null $pkce
     * @return Token
     */
    public function createFromAuthCode(
        string $state,
        string $authCode,
        PKCE|null $pkce = null
    ): Token {
        return $this
            ->validateState($state)
            ->createToken($state, [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'code_verifier' => $pkce?->verifier,
                'redirect_uri' => $this->config->redirectUri,
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret
            ]);
    }


    /**
     * @param string $refreshToken
     * @return Token
     */
    public function createFromRefreshToken(string $refreshToken): Token
    {
        return $this->createToken('', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret
        ]);
    }


    /**
     * @param string|null $state
     * @return array{string,string}
     */
    protected function generateStateNonce(string|null $state = null): array
    {
        $state ??= bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        $this->cache->save(
            $this->cache
                ->get($state)
                ->set($nonce)
                ->expiresAfter(30)
        );
        $this->cache->save(
            $this->cache
                ->get($nonce)
                ->set($state)
                ->expiresAfter(30)
        );

        return [$state, $nonce];
    }


    /**
     * @param string $state
     * @return static
     */
    protected function validateState(string $state): static
    {
        $cacheItem = $this->cache->get($state);
        return $cacheItem->isHit() && $this->cache->delete($cacheItem)
            ? $this
            : throw new OAuthException(["Invalid state: %s", $state]);
    }


    /**
     * @param string $state
     * @param array<string,string|null> $params
     * @return Token
     */
    protected function createToken(
        string $state,
        array $params
    ): Token {
        return $this->apiClient->sendApiRequest(
            'POST',
            $this->config->tokenUri,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $params,
            function (ResponseInterface $response) use ($state): Token {
                $values = ApiClient::jsonResponse($response);
                return new Token(
                    tokenType: Cast::toString($values['token_type'] ?? null),
                    scope: Cast::toString($values['scope'] ?? null),
                    expiresOn: match (true) {
                        isset($values['expires_on']) => Cast::toInt($values['expires_on']),
                        isset($values['expires_in']) => time() + Cast::toInt($values['expires_in']),
                        default => 0
                    },
                    accessToken: Cast::toValueOrNull(
                        $values['access_token'] ?? null,
                        Cast::toString(...)
                    ),
                    idToken: $this->createIdToken(
                        $state,
                        Cast::toValueOrNull($values['id_token'] ?? null, Cast::toString(...))
                    ),
                    refreshToken: Cast::toValueOrNull(
                        $values['refresh_token'] ?? null,
                        Cast::toString(...)
                    )
                );
            }
        );
    }


    /**
     * @param string $state
     * @param string|null $idToken
     * @return IdToken|null
     */
    protected function createIdToken(
        string $state,
        string|null $idToken
    ): IdToken|null {
        if ($idToken === null) {
            return null;
        }

        JWT::$leeway = 30;
        $values = Cast::toArray(JWT::decode($idToken, $this->jwks));
        $nonce = Cast::toString($values['nonce'] ?? '');
        $item = $this->cache->get($nonce);
        return ($item->isHit() && $item->get() === $state && $this->cache->delete($item))
            ? new IdToken($idToken, $values)
            : throw new OAuthException(["Invalid nonce: %s", $nonce]);
    }
}
