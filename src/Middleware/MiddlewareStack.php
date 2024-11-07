<?php

declare(strict_types=1);

namespace Gadget\Http\Middleware;

use Gadget\Util\Stack;
use Psr\Http\Server\MiddlewareInterface;

/**
 * @extends Stack<MiddlewareInterface>
 */
class MiddlewareStack extends Stack
{
    /**
     * @param MiddlewareInterface ...$middleware
     * @return static
     */
    public function addMiddleware(...$middleware): static
    {
        foreach ($middleware as $mw) {
            $this->push($mw);
        }
        return $this;
    }
}
