<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Cache;

use Gadget\Cache\CacheItemPool;
use Gadget\Cache\TypedCachePool;
use Gadget\Http\OAuth\Model\AuthCode;
use Psr\Cache\CacheItemInterface;

/** @extends TypedCachePool<AuthCode> */
class AuthCodeCache extends TypedCachePool
{
    /**
     * @param CacheItemPool $cache
     */
    public function __construct(CacheItemPool $cache)
    {
        parent::__construct($cache);
        $this->setExpiresAfter(30);
    }


    /**
     * @param mixed $v
     * @return AuthCode|null
     */
    protected function toValue(mixed $v): mixed
    {
        return ($v instanceof AuthCode) ? $v : null;
    }


    /**
     * @param CacheItemInterface $item
     * @return AuthCode|null
     */
    protected function create(CacheItemInterface $item): mixed
    {
        return null;
    }
}
