<?php

declare(strict_types=1);

namespace Gadget\Http\Cookie;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CookieMiddleware implements MiddlewareInterface
{
    public function __construct()
    {
        // load cookie jar
    }


    public function __destruct()
    {
        // store cookie jar
    }


    /**
     * @return CookieJarInterface
     */
    protected function getCookieJar(): CookieJarInterface
    {
        throw new \RuntimeException();
    }


    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $cookies = $this->getCookieJar()->getMatches(
            $request->getUri()->getScheme(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath()
        );
        if (count($cookies) > 0) {
            $request = $request->withHeader('Cookie', implode('; ', $cookies));
        }

        $response = $handler->handle($request);

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
