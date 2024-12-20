<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Fig\Http\Message\MessageFactoryInterface;
use Gadget\Cache\CacheInterface;
use Gadget\Http\Cookie\Cookie;
use Gadget\Http\Cookie\CookieJarInterface;
use Gadget\Http\Message\MessageHandlerInterface;
use Gadget\Http\Message\RequestBuilderInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client implements ClientInterface
{
    /**
     * @param PsrClientInterface $client
     * @param MessageFactoryInterface $messageFactory
     * @param MiddlewareContainerInterface $middlewareContainer
     * @param RequestBuilderInterface $requestBuilder
     * @param CacheInterface $cache
     * @param CookieJarInterface $cookieJar
     */
    public function __construct(
        private PsrClientInterface $client,
        private MessageFactoryInterface $messageFactory,
        private MiddlewareContainerInterface $middlewareContainer,
        private RequestBuilderInterface $requestBuilder,
        private CacheInterface $cache,
        private CookieJarInterface $cookieJar
    ) {
        $this->cache = $cache->withNamespace(static::class);

        $cachedCookies = $this->cache
            ->getCacheItem(CookieJarInterface::class)
            ->getCacheValue(fn(mixed $v) => is_array($v) ? $v : null) ?? [];

        foreach ($cachedCookies as $cookie) {
            if ($cookie instanceof Cookie && !$cookie->isExpired()) {
                $this->cookieJar->setCookie($cookie);
            }
        }
    }


    public function __destruct()
    {
        $this->cache
            ->getCacheItem(CookieJarInterface::class)
            ->set($this->cookieJar->clearExpired()->getCookies())
            ->saveCacheItem();
    }


    /**
     * @return PsrClientInterface
     */
    public function getClient(): PsrClientInterface
    {
        return $this->client;
    }


    /**
     * @return MessageFactoryInterface
     */
    public function getMessageFactory(): MessageFactoryInterface
    {
        return $this->messageFactory;
    }


    /**
     * @return MiddlewareContainerInterface
     */
    public function getMiddlewareContainer(): MiddlewareContainerInterface
    {
        return $this->middlewareContainer;
    }


    /**
     * @return RequestBuilderInterface
     */
    public function createRequestBuilder(): RequestBuilderInterface
    {
        return $this->requestBuilder->clone();
    }


    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }


    /**
     * @return CookieJarInterface
     */
    public function getCookieJar(): CookieJarInterface
    {
        return $this->cookieJar;
    }


    /**
     * @template TRequest
     * @template TResponse
     * @param MessageHandlerInterface<TRequest,TResponse> $handler
     * @param TRequest|null $requestBody
     * @return TResponse
     */
    public function handleMessage(
        MessageHandlerInterface $handler,
        mixed $requestBody
    ): mixed {
        return $handler->invoke(
            $this,
            $requestBody
        );
    }


    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $request = $this->getMessageFactory()->toServerRequest($request);


        $cookies = $this->getCookieJar()->getMatches(
            $request->getUri()->getScheme(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath()
        );
        if (count($cookies) > 0) {
            $request = $request->withHeader('Cookie', implode('; ', $cookies));
        }


        $response = (new MiddlewareHandler(
            $this->client,
            $this->getMiddlewareContainer()->getMiddleware($request)
        ))->handle($request);


        $cookies = $response->getHeader('Set-Cookie');
        foreach ($cookies as $c) {
            $cookie = Cookie::fromString($c);
            if ($cookie->getDomain() === null) {
                $cookie->setDomain($request->getUri()->getHost());
            }
            if (strpos($cookie->getPath(), '/') !== 0) {
                $cookie->setPath(Cookie::getCookiePath($request->getUri()->getPath()));
            }
            if (!$cookie->matchesDomain($request->getUri()->getHost())) {
                continue;
            }
            $this->getCookieJar()->setCookie($cookie);
        }


        return $response;
    }
}
