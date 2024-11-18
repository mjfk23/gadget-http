<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Gadget\Io\Cast;
use Gadget\Io\File;
use Gadget\Io\JSON;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CookieMiddleware implements MiddlewareInterface
{
    private CookieJar $cookieJar;


    /** @param string $cookieFile */
    public function __construct(
        private string $cookieFile,
        private bool $overrideRequestCookie = true
    ) {
        $this->cookieJar = new CookieJar();
        $this->load();
    }


    public function __destruct()
    {
        $this->save();
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
        if ($this->overrideRequestCookie || !$request->hasHeader('Cookie')) {
            /** @var ServerRequestInterface $request */
            $request = $this->cookieJar->withCookieHeader($request);
        }

        $response = $handler->handle($request);

        $this->cookieJar->extractCookies($request, $response);

        return $response;
    }


    /** @return void */
    public function load(): void
    {
        $this->cookieJar = new CookieJar(
            false,
            file_exists($this->cookieFile)
                ? array_filter(
                    Cast::toTypedArray(
                        JSON::decode(File::getContents($this->cookieFile)),
                        fn(mixed $cookie) => new SetCookie(Cast::toArray($cookie))
                    ),
                    fn(SetCookie $cookie) => $cookie->getExpires() !== null && $cookie->getExpires() > time()
                )
                : []
        );
    }


    /** @return void */
    public function save(): void
    {
        $data = [];
        /** @var SetCookie $cookie */
        foreach ($this->cookieJar as $cookie) {
            if (CookieJar::shouldPersist($cookie, true)) {
                $data[] = $cookie->toArray();
            }
        }

        $json = JSON::encode($data);
        if (false === \file_put_contents($this->cookieFile, $json, \LOCK_EX)) {
            throw new \RuntimeException("Unable to save file {$this->cookieFile}");
        }
    }
}
