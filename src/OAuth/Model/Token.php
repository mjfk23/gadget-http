<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

use Gadget\Io\Cast;

class Token implements \JsonSerializable
{
    /**
     * @param mixed $values
     * @return self
     */
    public static function create(mixed $values): self
    {
        $values = Cast::toArray($values);
        return new self(
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
            idToken: Cast::toValueOrNull(
                $values['id_token'] ?? null,
                Cast::toString(...)
            ),
            refreshToken: Cast::toValueOrNull(
                $values['refresh_token'] ?? null,
                Cast::toString(...)
            )
        );
    }


    /**
     * @param string $tokenType
     * @param string $scope
     * @param int $expiresOn
     * @param string|null $accessToken
     * @param string|null $idToken
     * @param string|null $refreshToken
     */
    public function __construct(
        public string $tokenType,
        public string $scope,
        public int $expiresOn,
        public string|null $accessToken = null,
        public string|null $idToken = null,
        public string|null $refreshToken = null
    ) {
    }


    /** @inheritdoc */
    public function jsonSerialize(): mixed
    {
        return array_filter([
            'token_type' => $this->tokenType,
            'scope' => $this->scope,
            'expires_on' => $this->expiresOn,
            'access_token' => $this->accessToken,
            'id_token' => $this->idToken,
            'refresh_token' => $this->refreshToken
        ]);
    }
}
