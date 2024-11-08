<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Middleware;

use Gadget\Http\OAuth\Cache\TokenCache;
use Gadget\Http\OAuth\Model\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessTokenMiddleware implements MiddlewareInterface
{
    /**
     * @param Config $config
     * @param TokenCache $cache
     */
    public function __construct(
        private Config $config,
        private TokenCache $cache
    ) {
    }


    /** @inheritdoc */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $requestKey = $request->getAttribute($this->config->tokenRequestAttr);

        $tokenCacheKey = match (true) {
            is_string($requestKey) => $requestKey,
            $requestKey === true => $this->config->tokenCacheKey,
            $requestKey === false => null,
            $request->getUri()->getHost() === $this->config->hostName => $this->config->tokenCacheKey,
            default => null
        };

        $accessToken = $tokenCacheKey !== null
            ? $this->cache->get($tokenCacheKey)?->accessToken
            : null;

        return $handler->handle(
            is_string($accessToken)
                ? $request->withHeader('Authorization', "Bearer {$accessToken}")
                : $request
        );
    }
}
