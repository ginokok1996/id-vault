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
 *          "post"
 *     }
 * )
 *
 * @ORM\Entity(repositoryClass="App\Repository\CreateGroupRepository")
 */
class DeleteGroup
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
     * @example e2984465-190a-4562-829e-a8cca81aa35d
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
     * @var string group id
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     */
    private $groupId;

    /**
     * @var array deleted group urls
     *
     * @example https://id-vault.com/api/v1/wac/groups/e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"read", "write"})
     */
    private $groups;

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

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }
}
