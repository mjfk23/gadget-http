<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

use Firebase\JWT\CachedKeySet;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class JWKS extends CachedKeySet
{
    public function __construct(
        OAuthConfig $config,
        ClientInterface $client,
        RequestFactoryInterface $factory,
        CacheItemPoolInterface $cache,
        int|null $expiresAfter = null,
        bool $rateLimit = false
    ) {
        parent::__construct(
            jwksUri: $config->jwksUri,
            httpClient: $client,
            httpFactory: $factory,
            cache: $cache,
            expiresAfter: $expiresAfter,
            rateLimit: $rateLimit,
            defaultAlg: $config->jwksDefaultAlg
        );
    }
}
