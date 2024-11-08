<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Cache;

use Gadget\Cache\CacheItemPool;
use Gadget\Cache\TypedCachePool;
use Gadget\Http\OAuth\Factory\TokenFactory;
use Gadget\Http\OAuth\Model\Token;
use Psr\Cache\CacheItemInterface;

/** @extends TypedCachePool<Token> */
class TokenCache extends TypedCachePool
{
    /**
     * @param CacheItemPool $cache
     * @param TokenFactory $factory
     */
    public function __construct(
        CacheItemPool $cache,
        private TokenFactory $factory
    ) {
        parent::__construct($cache);
    }


    /**
     * @param mixed $v
     * @return Token|null
     */
    protected function toValue(mixed $v): mixed
    {
        return ($v instanceof Token && ($v->expiresOn - 30 - time()) > 0)
            ? $v
            : null;
    }


    /**
     * @param CacheItemInterface $item
     * @return Token|null
     */
    protected function create(CacheItemInterface $item): mixed
    {
        $token = $item->isHit() ? $item->get() : null;
        $refreshToken = $token instanceof Token ? $token->refreshToken : null;
        return is_string($refreshToken)
            ? $this->factory->createFromRefreshToken($refreshToken)
            : null;
    }
}
