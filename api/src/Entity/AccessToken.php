<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AccessTokenRepository")
 * @ApiResource(
 *     attributes={"pagination_items_per_page"=30},
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "post"
 *     },
 *     itemOperations={
 *          "get",
 *          "put",
 *          "delete",
 *          "get_change_logs"={
 *              "path"="/access_tokens/{id}/change_log",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Changelogs",
 *                  "description"="Gets al the change logs for this resource"
 *              }
 *          },
 *          "get_audit_trail"={
 *              "path"="/access_tokens/{id}/audit_trail",
 *              "method"="get",
 *              "swagger_context" = {
 *                  "summary"="Audittrail",
 *                  "description"="Gets the audit trail for this resource"
 *              }
 *          }
 *     }
 * )
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 */
class AccessToken
{
    /**
     * @var UuidInterface The UUID identifier of this resource
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Assert\Uuid
     * @Groups({"read"})
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    private $id;

    /**
     * @var string The UUID identifier of this object
     *
     * @example authorization_code
     *
     * @Groups({"write"})
     */
    private $grant_type;

    /**
     * @var string The id of your application
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     */
    private $client_id;

    /**
     * @var string The goal of the request
     *
     * @example log in to commonground
     *
     * @Groups({"write"})
     * @ORM\Column(type="string", nullable=true)
     */
    private $goal;

    /**
     * @var string The secret of your application
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     */
    private $client_secret;

    /**
     * @var string The code given to your application on
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     */
    private $code;

    /**
     * @var string The transportation type of the token
     *
     * @example bearer
     *
     * @Groups({"read"})
     */
    private $token_type;

    /**
     * @var int The time in wisch the acces token will expire
     *
     * @example 3600
     *
     * @Groups({"read"})
     */
    private $expires_in;

    /**
     * @var string The scopes profided by the acces token
     *
     * @example user,claim.schema.person.birthdate
     *
     * @Groups({"read"})
     */
    private $scope;

    /**
     * @var string code used for userinfo endpoint
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read"})
     */
    private $access_token;

    /**
     * @var string A JWT reprecentation of the acces token
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read"})
     */
    private $id_token;

    /**
     * @var string A unique validator provided by your application to check the validaty of the call
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read","write"})
     */
    private $state;

    /**
     * @var bool Whether this user is new or not
     *
     * @example false
     *
     * @Groups({"read"})
     */
    private $newUser;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getGrantType(): ?string
    {
        return $this->grant_type;
    }

    public function setGrantType(string $grant_type): self
    {
        $this->grant_type = $grant_type;

        return $this;
    }

    public function setGrant_type(string $grant_type): self
    {
        $this->grant_type = $grant_type;

        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(string $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }

    public function setClient_id(string $client_id): self
    {
        $this->client_id = $client_id;

        return $this;
    }

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->client_secret;
    }

    public function setClientSecret(string $client_secret): self
    {
        $this->client_secret = $client_secret;

        return $this;
    }

    public function setClient_secret(string $client_secret): self
    {
        $this->client_secret = $client_secret;

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
        return $this->token_type;
    }

    public function setTokenType(string $token_type): self
    {
        $this->token_type = $token_type;

        return $this;
    }

    public function setToken_type(string $token_type): self
    {
        $this->token_type = $token_type;

        return $this;
    }

    public function getExpiresIn(): ?string
    {
        return $this->expires_in;
    }

    public function setExpiresIn(string $expires_in): self
    {
        $this->expires_in = $expires_in;

        return $this;
    }

    public function setExpires_in(string $expires_in): self
    {
        $this->expires_in = $expires_in;

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
        return $this->access_token;
    }

    public function setAccessToken(string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    public function setAccess_token(string $access_token): self
    {
        $this->access_token = $access_token;

        return $this;
    }

    public function getIdToken(): ?string
    {
        return $this->id_token;
    }

    public function setIdToken(string $id_token): self
    {
        $this->id_token = $id_token;

        return $this;
    }

    public function setId_token(string $id_token): self
    {
        $this->id_token = $id_token;

        return $this;
    }

    public function getNewUser(): ?bool
    {
        return $this->newUser;
    }

    public function setNewUser(bool $newUser): self
    {
        $this->newUser = $newUser;

        return $this;
    }
}
