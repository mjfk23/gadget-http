<?php

declare(strict_types=1);

namespace Gadget\Http\OAuth\Model;

class Token implements \JsonSerializable
{
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
        $values = [
            'token_type' => $this->tokenType,
            'scope' => $this->scope,
            'expires_on' => $this->expiresOn,
            'access_token' => $this->accessToken
        ];
        if ($this->idToken !== null) {
            $values['id_token'] = $this->idToken;
        }
        if ($this->refreshToken !== null) {
            $values['refresh_token'] = $this->refreshToken;
        }
        return $values;
    }
}
