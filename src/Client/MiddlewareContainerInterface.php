<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareContainerInterface
{
    /**
     * @param ServerRequestInterface|null $request
     * @return list<MiddlewareInterface>
     */
    public function getMiddleware(ServerRequestInterface|null $request = null): array;


    /**
     * @param MiddlewareInterface[] $middleware
     * @return static
     */
    public function setMiddleware(array $middleware): static;
}
