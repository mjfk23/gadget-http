<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Cache\CacheItemPool;
use Gadget\Http\Cookie\Cookie;
use Gadget\Http\Cookie\CookieJar;
use Gadget\Http\Exception\ClientException;
use Gadget\Http\Exception\RequestException;
use Gadget\Http\Exception\ResponseException;
use Gadget\Http\Message\MessageFactory;
use Gadget\Http\Message\MessageHandler;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

class Client implements ClientInterface
{
    private CookieJar|null $cookieJar = null;


    /**
     * @param ClientInterface $client
     * @param MessageFactory $messageFactory
     * @param CacheItemPool $cache
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private ClientInterface $client,
        private MessageFactory $messageFactory,
        private CacheItemPool $cache,
        private array $middleware = []
    ) {
        $this->cache = $cache->withNamespace(self::class);
    }


    public function __destruct()
    {
        if ($this->cookieJar !== null) {
            $cacheItem = $this->cache->get('cookieJar');
            $this->cache->save($cacheItem->set($this->cookieJar->clearExpired()));
        }
    }


    /**
     * @template TResponse
     * @param MessageHandler<TResponse> $handler
     * @return TResponse
     */
    public function invoke(MessageHandler $handler): mixed
    {
        try {
            $handler->setClient($this);

            try {
                $request = $handler->getRequest();
            } catch (\Throwable $t) {
                throw new RequestException("Error building request", 0, $t);
            }

            try {
                $response = $this->sendRequest($request);
                $handler->setResponse($response);
            } catch (\Throwable $t) {
                throw new ClientException([
                    "Error sending request: %s %s",
                    $request->getMethod(),
                    $request->getUri()
                ], 0, $t);
            }

            try {
                return $handler->handleResponse();
            } catch (\Throwable $t) {
                throw new ResponseException([
                    "Error handling response: %s %s => %s",
                    $request->getMethod(),
                    $request->getUri(),
                    $response->getStatusCode()
                ], 0, $t);
            }
        } catch (\Throwable $t) {
            return $handler->handleError($t);
        }
    }


    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Find matching cookies from the cookie jar and add to request
        $cookies = $this->getCookieJar()->getMatches(
            $request->getUri()->getScheme(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath()
        );

        if (count($cookies) > 0) {
            $request = $request->withHeader('Cookie', implode('; ', $cookies));
        }

        // Send request/response through middleware stack
        $response = (new MiddlewareHandler($this->client, $this->middleware))
            ->handle($this->getMessageFactory()->toServerRequest($request));

        // Pull cookies from response and add to cookie jar
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


    /**
     * @return MessageFactory
     */
    public function getMessageFactory(): MessageFactory
    {
        return $this->messageFactory;
    }


    /**
     * @return CacheItemPool
     */
    public function getCache(): CacheItemPool
    {
        return $this->cache;
    }


    /**
     * @return CookieJar
     */
    public function getCookieJar(): CookieJar
    {
        if ($this->cookieJar === null) {
            $cacheItem = $this->cache->get('cookieJar');
            $cookieJar = $cacheItem->isHit() ? $cacheItem->get() : null;
            if (!$cookieJar instanceof CookieJar) {
                $cookieJar = new CookieJar();
                $this->cache->save($cacheItem->set($cookieJar));
            }
            $this->cookieJar = $cookieJar;
        }

        return $this->cookieJar;
    }


    /**
     * @return MiddlewareInterface[]
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }


    /**
     * @param MiddlewareInterface[] $middleware
     * @return static
     */
    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }
}
