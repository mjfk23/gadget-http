<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Fig\Http\Message\MessageFactoryInterface;
use Gadget\Cache\CacheInterface;
use Gadget\Http\Cookie\CookieJarInterface;
use Gadget\Http\Message\MessageHandlerInterface;
use Gadget\Http\Message\RequestBuilderInterface;
use Psr\Http\Client\ClientInterface as PsrClientInterface;

interface ClientInterface extends PsrClientInterface
{
    /**
     * @return PsrClientInterface
     */
    public function getClient(): PsrClientInterface;


    /**
     * @return MessageFactoryInterface
     */
    public function getMessageFactory(): MessageFactoryInterface;


    /**
     * @return MiddlewareContainerInterface
     */
    public function getMiddlewareContainer(): MiddlewareContainerInterface;


    /**
     * @return RequestBuilderInterface
     */
    public function createRequestBuilder(): RequestBuilderInterface;


    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface;


    /**
     * @return CookieJarInterface
     */
    public function getCookieJar(): CookieJarInterface;


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
    ): mixed;
}
