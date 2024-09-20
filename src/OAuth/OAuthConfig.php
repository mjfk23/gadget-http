<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth;

final class OAuthConfig
{
    /**
     * @param string $jwksUri
     * @param string $authCodeUri
     * @param string $tokenUri
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $scope
     * @param string $keyAttrName
     * @param string $defaultKey
     */
    public function __construct(
        public string $authCodeUri,
        public string $tokenUri,
        public string $clientId,
        public string $clientSecret,
        public string $redirectUri,
        public string $scope,
        public string $jwksUri = '',
        public string $jwksDefaultAlg = 'RS256',
        public string $keyAttrName = 'oauthTokenKey',
        public string $defaultKey = 'default'
    ) {
    }
}
