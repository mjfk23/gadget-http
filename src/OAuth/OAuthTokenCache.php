<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class OAuthTokenCache
{
    /**
     * @param CacheItemPoolInterface $cache
     * @param OAuthTokenFactory $factory
     */
    public function __construct(
        private CacheItemPoolInterface $cache,
        private OAuthTokenFactory $factory,
    ) {
    }


    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }


    /**
     * @param string $key
     * @return OAuthToken|null
     */
    public function get(string $key): OAuthToken|null
    {
        $item = $this->getItem($key);
        $token = $this->getToken($item);
        return ($token !== null && ($token->expiresOn - time() < 30))
            ? $this->setToken(
                $item,
                $token->refreshToken !== null
                    ? $this->factory->fromRefreshToken($token->refreshToken)
                    : null
            )
            : $token;
    }


    /**
     * @param string $key
     * @param OAuthToken|null $token
     * @return OAuthToken|null
     */
    public function set(
        string $key,
        OAuthToken|null $token
    ): OAuthToken|null {
        return $this->setToken(
            $this->getItem($key),
            $token
        );
    }


    /**
     * @param string $key
     * @return string
     */
    private function getKey(string $key): string
    {
        return hash('SHA256', sprintf('%s::%s', self::class, $key));
    }


    /**
     * @param string $key
     * @return CacheItemInterface
     */
    private function getItem(string $key): CacheItemInterface
    {
        return $this->cache->getItem($this->getKey($key));
    }


    /**
     * @param CacheItemInterface $item
     * @return OAuthToken|null
     */
    private function getToken(CacheItemInterface $item): OAuthToken|null
    {
        /** @var mixed $token */
        $token = $item->isHit() ? $item->get() : null;
        return $token instanceof OAuthToken ? $token : null;
    }


    /**
     * @param CacheItemInterface $item
     * @param OAuthToken|null $token
     * @return OAuthToken|null
     */
    private function setToken(
        CacheItemInterface $item,
        OAuthToken|null $token
    ): OAuthToken|null {
        if ($token === null) {
            $this->cache->deleteItem($item->getKey());
        } else {
            $this->cache->save($item->set($token));
        }
        return $token;
    }
}
