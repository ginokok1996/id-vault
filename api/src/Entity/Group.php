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
 *     		"get"={
 *     			"method"="GET",
 *     			"path"="/groups"
 *     		},
 *     		"get_group"={
 *     			"method"="GET",
 *     			"path"="/groups/{id}",
 *     		}
 *     },
 *     itemOperations={
 *     		"get"={
 *     			"method"="GET",
 *     			"path"="/groups/uuid/{id}"
 *     		}
 *     }
 * )
 *
 *
 *
 * @ORM\Entity(repositoryClass="App\Repository\GroupRepository")
 *
 * @ORM\Table(name="`group`")
 */
class Group
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
     * @var string client id
     *
     * @example test@user.nl
     *
     * @Groups({"write"})
     */
    private $clientId;

    /**
     * @var string organization uri
     *
     * @example test@user.nl
     *
     * @Groups({"write"})
     */
    private $organization;

    /**
     * @var array array of groups
     *
     * @example
     *
     * @Groups({"read"})
     * @ORM\Column(type="json")
     */
    private $groups = [];

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getClientId(): ?string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): self
    {
        $this->clientId = $clientId;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function setGroups(?array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }
}
