<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ApiResource(
 *     normalizationContext={"groups"={"read"}, "enable_max_depth"=true},
 *     denormalizationContext={"groups"={"write"}, "enable_max_depth"=true}
 * )
 * @ORM\Entity(repositoryClass="App\Repository\DossierRepository")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 */
class Dossier
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
     * @var string Name of the Dossier
     * @Assert\NotNull
     * @example employee dossier
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $name;

    /**
     * @var string The description of a the dossier
     *
     * @example employee dossier of henk
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $description;

    /**
     * @var string The goal of a the dossier
     * @Assert\NotNull
     * @example employee dossier of henk
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $goal;

    /**
     * @Assert\NotNull

     * @example 27-10-2020 10:47:00
     *
     * @Groups({"read","write"})
     */
    private $expiryDate;

    /**
     * @var string A URL with which the user can view this Dossier.
     *
     * @example https://dev.id-vault.com/dossiers/x (?)
     *
     * @Assert\NotNull
     * @Assert\Url
     * @Assert\Length(
     *     max = 255
     * )
     * @Groups({"read", "write"})
     */
    private $sso;

    /**
     * @var bool whether or not to this Dossier is on legal basis.
     *
     * @example true
     *
     * @Assert\Type("bool")
     * @Groups({"read", "write"})
     */
    private $legal = false;

    /**
     * @var array scopes this authorization has access to
     * @Assert\NotNull

     * @Groups({"read","write"})
     */
    private $scopes = [];

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getGoal(): ?string
    {
        return $this->goal;
    }

    public function setGoal(?string $goal): self
    {
        $this->goal = $goal;

        return $this;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTimeInterface $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    public function getSso(): ?string
    {
        return $this->sso;
    }

    public function setSso(?string $sso): self
    {
        $this->sso = $sso;

        return $this;
    }

    public function getLegal(): ?bool
    {
        return $this->legal;
    }

    public function setLegal(?bool $legal): self
    {
        $this->legal = $legal;

        return $this;
    }

    public function getScopes(): ?array
    {
        return $this->scopes;
    }

    public function setScopes(?array $scopes): self
    {
        $this->scopes = $scopes;

        return $this;
    }

}
