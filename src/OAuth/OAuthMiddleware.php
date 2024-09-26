<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OAuthMiddleware implements MiddlewareInterface
{
    /**
     * @param OAuthTokenCache $cache
     * @param OAuthConfig $config
     */
    public function __construct(
        private OAuthTokenCache $cache,
        private OAuthConfig $config
    ) {
    }


    /**
     * @param mixed $key
     * @return OAuthToken|null
     */
    public function getToken(mixed $key): OAuthToken|null
    {
        return is_string($key)
            ? $this->cache->get($key)
            : null;
    }


    /** @inheritdoc */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $this->getToken($request->getAttribute($this->config->keyAttrName));
        if ($token instanceof OAuthToken && is_string($token->accessToken)) {
            $request = $request->withHeader(
                'Authorization',
                sprintf("Bearer %s", $token->accessToken)
            );
        }

        return $handler->handle($request);
    }
}
