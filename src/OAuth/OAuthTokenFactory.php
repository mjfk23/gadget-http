<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

use Gadget\Http\Exception\OAuthException;
use Gadget\Factory\AbstractFactory;
use Gadget\Io\Cast;
use Gadget\Http\ApiClient;
use Psr\Cache\CacheItemPoolInterface;

/** @extends AbstractFactory<OAuthToken> */
class OAuthTokenFactory extends AbstractFactory
{
    /**
     * @param OAuthConfig $config
     * @param ApiClient $apiClient
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(
        public OAuthConfig $config,
        protected ApiClient $apiClient,
        protected CacheItemPoolInterface $cache
    ) {
        parent::__construct(OAuthToken::class);
    }


    /**
     * @param string|null $state
     * @param PKCEToken|null $pkce
     * @return array{string,string,string}
     */
    public function getAuthCodeUri(
        string|null $state = null,
        PKCEToken|null $pkce = null
    ): array {
        $state ??= bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));

        $this->cache->save(
            $this->cache
                ->getItem(hash('SHA256', sprintf('%s::%s', self::class, $state)))
                ->set($nonce)
                ->expiresAfter(30)
        );

        return [
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
            $nonce
        ];
    }


    /**
     * @param string $state
     * @param string $authCode
     * @param PKCEToken|null $pkce
     * @return OAuthToken
     */
    public function fromAuthCode(
        string $state,
        string $authCode,
        PKCEToken|null $pkce = null
    ): OAuthToken {
        $item = $this->cache->getItem(hash('SHA256', sprintf('%s::%s', self::class, $state)));
        if (!$item->isHit()) {
            throw new OAuthException(["Invalid state: %s", $state]);
        }
        $this->cache->deleteItem($item->getKey());

        return $this->create($this->fetch([
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'code_verifier' => $pkce?->verifier,
            'redirect_uri' => $this->config->redirectUri,
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret
        ]));
    }


    /**
     * @param string $refreshToken
     * @return OAuthToken
     */
    public function fromRefreshToken(string $refreshToken): OAuthToken
    {
        return $this->create($this->fetch([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret
        ]));
    }


    /** @inheritdoc */
    public function create(mixed $values): object
    {
        $values = Cast::toArray($values);
        return new OAuthToken(
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
            idToken: Cast::toValueOrNull(
                $values['id_token'] ?? null,
                Cast::toString(...)
            ),
            refreshToken: Cast::toValueOrNull(
                $values['refresh_token'] ?? null,
                Cast::toString(...)
            )
        );
    }


    /**
     * @param mixed[] $params
     * @return mixed[]
     */
    protected function fetch(array $params): array
    {
        return $this->apiClient->sendApiRequest(
            'POST',
            $this->config->tokenUri,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $params
        );
    }
}
