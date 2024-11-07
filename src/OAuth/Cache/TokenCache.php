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
        return ((($token?->expiresOn ?? 0) - time()) > 30)
            ? $token
            : $this->refresh($item, $token);
    }


    /**
     * @param CacheItemInterface $item
     * @param Token|null $token
     * @return Token|null
     */
    private function refresh(
        CacheItemInterface $item,
        Token|null $token
    ): Token|null {
        if ($token !== null && $token->refreshToken !== null) {
            $this->cache->save($item->set($this->factory->createFromRefreshToken($token->refreshToken)));
        } else {
            $this->cache->delete($item);
        }
        return $token;
    }
}
