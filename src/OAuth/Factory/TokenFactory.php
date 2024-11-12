<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Firebase\JWT\JWT;
use Gadget\Http\Exception\OAuthException;
use Gadget\Http\ApiClient;
use Gadget\Http\OAuth\Cache\CachedKeySet;
use Gadget\Http\OAuth\Model\AuthCode;
use Gadget\Http\OAuth\Model\Config;
use Gadget\Http\OAuth\Model\IdToken;
use Gadget\Http\OAuth\Model\Token;
use Gadget\Io\Cast;
use Psr\Http\Message\ResponseInterface;

final class TokenFactory
{
    /**
     * @param Config $config
     * @param ApiClient $apiClient
     * @param CachedKeySet $jwks
     */
    public function __construct(
        private Config $config,
        private ApiClient $apiClient,
        private CachedKeySet $jwks
    ) {
    }


    /**
     * @param AuthCode $authCode
     * @return Token
     */
    public function createFromAuthCode(AuthCode $authCode): Token
    {
        return $this->create(
            [
                'grant_type' => 'authorization_code',
                'code' => $authCode->code ?? throw new \RuntimeException("Missing authorization code"),
                'code_verifier' => $authCode->pkce?->verifier,
                'redirect_uri' => $this->config->redirectUri,
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret
            ],
            fn(array $values): IdToken|null => $this->createIdToken(
                $authCode,
                Cast::toValueOrNull($values['id_token'] ?? null, Cast::toString(...))
            )
        );
    }


    /**
     * @param string $refreshToken
     * @return Token
     */
    public function createFromRefreshToken(string $refreshToken): Token
    {
        return $this->create(
            [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->config->clientId,
                'client_secret' => $this->config->clientSecret
            ],
            fn(): IdToken|null => null
        );
    }


    /**
     * @param array<string,string|null> $params
     * @param (callable(mixed[] $values):(IdToken|null)) $createIdToken
     * @return Token
     */
    private function create(
        array $params,
        callable $createIdToken
    ): Token {
        return $this->apiClient->sendApiRequest(
            method: 'POST',
            uri: $this->config->tokenUri,
            parseResponse: fn (ResponseInterface $response): Token => $this->createToken(
                ApiClient::jsonResponse($response),
                $createIdToken
            ),
            headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
            body: $params,
        );
    }


    /**
     * @param mixed[] $values
     * @param (callable(mixed[] $values):(IdToken|null)) $createIdToken
     * @return Token
     */
    private function createToken(
        array $values,
        callable $createIdToken
    ): Token {
        return new Token(
            tokenType: Cast::toString($values['token_type'] ?? null),
            scope: Cast::toString($values['scope'] ?? null),
            expiresOn: match (true) {
                isset($values['expires_on']) => Cast::toInt($values['expires_on']),
                isset($values['expires_in']) => time() + Cast::toInt($values['expires_in']),
                default => 0
            },
            accessToken: Cast::toValueOrNull(
                $values['access_token'] ?? null,
                Cast::toString(...)
            ),
            idToken: $createIdToken($values),
            refreshToken: Cast::toValueOrNull(
                $values['refresh_token'] ?? null,
                Cast::toString(...)
            )
        );
    }


    /**
     * @param AuthCode $authCode
     * @param string|null $idToken
     * @return IdToken|null
     */
    private function createIdToken(
        AuthCode $authCode,
        string|null $idToken
    ): IdToken|null {
        if ($idToken === null) {
            return null;
        }

        JWT::$leeway = 30;
        $values = Cast::toArray(JWT::decode($idToken, $this->jwks));
        $nonce = Cast::toString($values['nonce'] ?? '');
        return $authCode->nonce === $nonce
            ? new IdToken($idToken, $values)
            : throw new OAuthException(["Invalid nonce: %s", $nonce]);
    }
}
