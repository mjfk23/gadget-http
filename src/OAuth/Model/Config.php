<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

class Config
{
    /**
     * @param string $hostName
     * @param string $jwksUri
     * @param string $authCodeUri
     * @param string $tokenUri
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $scope
     * @param string $tokenRequestAttr
     * @param string $tokenCacheKey
     */
    public function __construct(
        public string $hostName,
        public string $authCodeUri,
        public string $tokenUri,
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public string $scope,
        public string $jwksUri = '',
        public string $jwksDefaultAlg = 'RS256',
        public string $tokenRequestAttr = self::class . '::oauthRequestAttr',
        public string $tokenCacheKey = self::class . '::oauthCacheKey'
    ) {
    }
}
