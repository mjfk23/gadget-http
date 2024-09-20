<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

use Firebase\JWT\JWT;
use Gadget\Http\Exception\OAuthInvalidIDTokenException;
use Gadget\Http\Exception\OAuthInvalidNonceException;
use Gadget\Io\Cast;
use Psr\Cache\CacheItemPoolInterface;

final class OIDCTokenFactory
{
    /**
     * @param OAuthTokenFactory $factory
     * @param JWKS $jwks
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(
        private OAuthTokenFactory $factory,
        private JWKS $jwks,
        private CacheItemPoolInterface $cache
    ) {
    }


    /**
     * @param string|null $state
     * @return array{string,string,string}
     */
    public function getAuthCodeUri(string|null $state = null): array
    {
        list(, $state, $nonce) = $authCodeUri = $this->factory->getAuthCodeUri($state);

        $this->cache->save(
            $this->cache
                ->getItem(hash('SHA256', sprintf('%s::%s', self::class, $nonce)))
                ->set($state)
                ->expiresAfter(30)
        );

        return $authCodeUri;
    }


    /**
     * @param string $state
     * @param string $authCode
     * @param PKCEToken|null $pkce
     * @return array{OAuthToken,mixed[]}
     */
    public function fromAuthCode(
        string $state,
        string $authCode,
        PKCEToken|null $pkce = null
    ): array {
        $token = $this->factory->fromAuthCode($state, $authCode, $pkce);
        $jwt = $this->validateIdToken($token);

        $nonce = Cast::toString($jwt['nonce'] ?? '');
        $item = $this->cache->getItem(hash('SHA256', sprintf('%s::%s', self::class, $nonce)));
        if (!$item->isHit() || $item->get() !== $state) {
            throw new OAuthInvalidNonceException();
        }
        $this->cache->deleteItem($item->getKey());

        return [$token, $jwt];
    }


    /**
     * @param OAuthToken $token
     * @return mixed[]
     */
    public function validateIdToken(OAuthToken $token): array
    {
        JWT::$leeway = 30;
        return Cast::toArray(JWT::decode(
            $token->idToken ?? throw new OAuthInvalidIDTokenException(),
            $this->jwks
        ));
    }
}
