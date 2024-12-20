<?php

declare(strict_types=1);

namespace Gadget\Http\Cookie;

interface CookieJarInterface
{
    public function getDefaultMagAge(): int|null;


    public function setDefaultMagAge(int|null $defaultMaxAge): static;


    /**
     * @return Cookie[]
     */
    public function getCookies(): array;


    /**
     * @param (string|array<string,string|int|bool|null>|Cookie)[] $cookies
     * @return static
     */
    public function setCookies(array $cookies): static;


    /**
     * @param string $domain
     * @param string $path
     * @param string $name
     * @return Cookie
     */
    public function getCookie(string $domain, string $path, string $name): Cookie|null;


    /**
     * @param string|array<string,string|int|bool|null>|Cookie $cookie
     * @return bool
     */
    public function setCookie(string|array|Cookie $cookie): bool;


    /**
     * @return \Traversable<string,Cookie>
     */
    public function getIterator(): \Traversable;


    /**
     * @param string $scheme
     * @param string $host
     * @param string $path
     * @return Cookie[]
     */
    public function getMatches(
        string $scheme,
        string $host,
        string $path
    ): array;


    /**
     * @return static
     */
    public function clearExpired(): static;
}
