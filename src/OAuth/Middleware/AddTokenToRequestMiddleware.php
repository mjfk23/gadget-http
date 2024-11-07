<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Middleware;

use Gadget\Http\OAuth\Cache\TokenCache;
use Gadget\Http\OAuth\Model\Config;
use Gadget\Http\OAuth\Model\Token;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AddTokenToRequestMiddleware implements MiddlewareInterface
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
        $key = $request->getAttribute($this->config->oauthRequestAttr);
        $token = is_string($key) ? $this->cache->get($key) : null;
        return $handler->handle(
            ($token instanceof Token && is_string($token->accessToken))
                ? $request->withHeader('Authorization', "Bearer {$token->accessToken}")
                : $request
        );
    }
}
