<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true},
 *     collectionOperations={
 *          "post",
 *          "client_configuration"={
                "method"="GET",
 *              "path"="/client_configuration/{application}"
 *          }
 *     }
 * )
 *
 */
class CreateClient
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
     * @Groups({"read", "write"})
     * @ORM\Column(name="client_name", type="string", length=255)
     */
    private $client_name;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(name="initiate_login_uri", type="string", length=255)
     */
    private $initiate_login_uri;

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(nullable=false, type="array")
     *
     */
    private $contacts = [];

    /**
     * @Groups({"read", "write"})
     * @ORM\Column(nullable=false, type="array")
     */
    private $redirect_uris = [];

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->client_name;
    }

    public function setClientName(?string $clientName): self
    {
        $this->client_name = $clientName;

        return $this;
    }

    public function setClient_name(?string $clientName): self
    {
        $this->client_name = $clientName;

        return $this;
    }

    public function getInitiateLoginUri(): ?string
    {
        return $this->initiate_login_uri;
    }

    public function setInitiateLoginUri(?string $initiateLoginUri): self
    {
        $this->initiate_login_uri = $initiateLoginUri;

        return $this;
    }

    public function setInitiate_login_uri(?string $initiateLoginUri): self
    {
        $this->initiate_login_uri = $initiateLoginUri;

        return $this;
    }

    public function getRedirectUris(): ?array
    {
        return $this->redirect_uris;
    }

    public function setRedirectUris(?array $redirectUris): self
    {
        $this->redirect_uris = $redirectUris;

        return $this;
    }
    public function setRedirect_uris(?array $redirectUris): self
    {
        $this->redirect_uris = $redirectUris;

        return $this;
    }


    public function getContacts(): ?array
    {
        return $this->contacts;
    }

    public function setContacts(?array $contacts): self
    {
        $this->contacts = $contacts;

        return $this;
    }
}
