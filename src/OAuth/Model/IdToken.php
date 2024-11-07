<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

class IdToken implements \Stringable
{
    /**
     * @param string $raw
     * @param mixed[] $values
     */
    public function __construct(
        public string $raw,
        public array $values = []
    ) {
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->raw;
    }
}
