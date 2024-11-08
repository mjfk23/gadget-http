<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Cache;

use Gadget\Cache\CacheItemPool;
use Gadget\Http\OAuth\Factory\TokenFactory;
use Gadget\Http\OAuth\Model\Token;
use Psr\Cache\CacheItemInterface;

class TokenCache
{
    /**
     * @param TokenFactory $factory
     * @param CacheItemPool $cache
     */
    public function __construct(
        private TokenFactory $factory,
        private CacheItemPool $cache
    ) {
        $this->cache = $cache->withNamespace(self::class);
    }


    /**
     * @param string $key
     * @return Token|null
     */
    public function get(string $key): Token|null
    {
        $item = $this->cache->get($key);
        $token = $item->isHit() ? $item->get() : null;
        $token = $token instanceof Token ? $token : null;

        return match (true) {
            ((($token?->expiresOn ?? 0) - 30) - time()) > 0 => $token,
            is_string($token?->refreshToken) => $this->set(
                $key,
                $this->factory->createFromRefreshToken($token->refreshToken)
            ),
            default => null
        };
    }


    /**
     * @param string $key
     * @param Token $token
     * @return Token
     */
    public function set(
        string $key,
        Token $token
    ): Token {
        $this->cache->save($this->cache->get($key)->set($token));
        return $token;
    }
}
