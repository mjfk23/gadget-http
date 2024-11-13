<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Factory;

use Gadget\Http\Client\ApiCaller;
use Gadget\Http\Client\ApiClient;
use Gadget\Http\OAuth\Model\AuthCode;
use Gadget\Http\OAuth\Model\Config;
use Gadget\Http\OAuth\Model\Token;
use Psr\Http\Message\ResponseInterface;

final class TokenFactory
{
    /**
     * @param Config $config
     * @param ApiClient $apiClient
     */
    public function __construct(
        private Config $config,
        private ApiClient $apiClient
    ) {
    }


    /**
     * @param AuthCode $authCode
     * @return Token
     */
    public function createFromAuthCode(AuthCode $authCode): Token
    {
        return $this->create([
            'grant_type' => 'authorization_code',
            'code' => $authCode->code ?? throw new \RuntimeException("Missing authorization code"),
            'code_verifier' => $authCode->pkce?->verifier,
            'redirect_uri' => $this->config->redirectUri,
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret
        ]);
    }


    /**
     * @param string $refreshToken
     * @return Token
     */
    public function createFromRefreshToken(string $refreshToken): Token
    {
        return $this->create([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret
        ]);
    }


    /**
     * @param array<string,string|null> $params
     * @return Token
     */
    private function create(array $params): Token
    {
        return $this->apiClient->sendApiRequest(new ApiCaller(
            method: 'POST',
            uri: $this->config->tokenUri,
            headers: ['Content-Type' => 'application/x-www-form-urlencoded'],
            body: $params,
            createResponse: $this->createToken(...)
        ));
    }


    /**
     * @param ResponseInterface $response
     * @return Token
     */
    private function createToken(ResponseInterface $response): Token
    {
        return ($response->getStatusCode() === 200)
            ? ApiCaller::createResponseFromJson($response, Token::create(...))
            : throw new \RuntimeException();
    }
}
