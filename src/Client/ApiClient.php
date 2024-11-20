<?php

declare(strict_types=1);

namespace Gadget\Http\Client;

abstract class ApiClient
{
    /**
     * @param Client $client
     */
    public function __construct(private Client $client)
    {
    }


    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }


    /**
     * @param Client $client
     * @return static
     */
    public function setClient(Client $client): static
    {
        $this->client = $client;
        return $this;
    }
}
