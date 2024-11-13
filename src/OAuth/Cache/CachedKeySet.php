<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Cache;

use Firebase\JWT\CachedKeySet as BaseCachedKeySet;
use Gadget\Cache\CacheItemPool;
use Gadget\Http\Client\HttpClient;
use Gadget\Http\OAuth\Model\Config;

class CachedKeySet extends BaseCachedKeySet
{
    public function __construct(
        Config $config,
        HttpClient $client,
        CacheItemPool $cache,
        int|null $expiresAfter = null,
        bool $rateLimit = false
    ) {
        parent::__construct(
            jwksUri: $config->jwksUri,
            httpClient: $client,
            httpFactory: $client,
            cache: $cache->getCacheItemPoolInterface(),
            expiresAfter: $expiresAfter,
            rateLimit: $rateLimit,
            defaultAlg: $config->jwksDefaultAlg
        );
    }
}
