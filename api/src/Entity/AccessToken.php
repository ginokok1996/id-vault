<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\AccessTokenRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;


/**
 * @ApiResource()
 */
class AccessToken
{
    /**
     */
    private $id;

    /**
     * @var UuidInterface The UUID identifier of this object
     *
     * @example authorization_code
     *
     * @Groups({"write"})
     * @Assert\NotNull
     * @Assert\Choice({"authorization_code"})
     */
    private $grantType;

    /**
     * @var UuidInterface The id of your application
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     * @Assert\Uuid
     * @Assert\NotNull
     */
    private $clientId;

    /**
     * @var UuidInterface The secret of your application
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     * @Assert\Uuid
     * @Assert\NotNull
     */
    private $clientSecret;

    /**
     * @var UuidInterface The code given to your application on
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     * @Assert\Uuid
     * @Assert\NotNull
     */
    private $code;

    /**
     * @var string The transportation type of the token
     *
     * @example bearer
     *
     * @Groups({"read"})
     */
    private $tokenType;

    /**
     * @var integer The time in wisch the acces token will expire
     *
     * @example 3600
     *
     * @Groups({"read"})
     */
    private $expiresIn;

    /**
     * @var string The scopes profided by the acces token
     *
     * @example user,claim.schema.person.birthdate
     *
     * @Groups({"read"})
     */
    private $scope;

    /**
     * @var string A JWT reprecentation of the acces token
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read"})
     */
    private $accessToken;

    /**
     * @var string A unique validator provided by your application to check the validaty of the call
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read","write"})
     */
    private $state;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrantType(): ?string
    {
        return $this->grantType;
    }

    public function setGrantType(string $grantType): self
    {
        $this->grantType = $grantType;

        return $this;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): self
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    public function setTokenType(string $tokenType): self
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    public function getExpiresIn(): ?string
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(string $expiresIn): self
    {
        $this->expiresIn = $expiresIn;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
