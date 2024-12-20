<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareContainer implements MiddlewareContainerInterface
{
    /**
     * @var list<MiddlewareInterface> $middleware
     */
    private array $middleware = [];


    public function __construct(MiddlewareInterface ...$middleware)
    {
        $this->setMiddleware($middleware);
    }


    /**
     * @param ServerRequestInterface|null|null $request
     * @return list<MiddlewareInterface>
     */
    public function getMiddleware(ServerRequestInterface|null $request = null): array
    {
        return $this->middleware;
    }


    /**
     * @param MiddlewareInterface[] $middleware
     * @return static
     */
    public function setMiddleware(array $middleware): static
    {
        $this->middleware = array_values($middleware);
        return $this;
    }


    /**
     * @param MiddlewareInterface ...$middleware
     * @return static
     */
    public function addMiddleware(MiddlewareInterface ...$middleware): static
    {
        $this->middleware = [
            ...$this->middleware,
            ...array_values($middleware)
        ];
        return $this;
    }
}
