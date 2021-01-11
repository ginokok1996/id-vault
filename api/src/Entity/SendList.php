<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
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
 * @ORM\Entity(repositoryClass="App\Repository\SendListRepository")
 *
 * @ApiFilter(BooleanFilter::class)
 * @ApiFilter(OrderFilter::class)
 * @ApiFilter(DateFilter::class, strategy=DateFilter::EXCLUDE_NULL)
 * @ApiFilter(SearchFilter::class)
 */
class SendList
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
     * @var string The action type
     *
     * @example getLists
     *
     * @Assert\Choice({"getLists", "createList", "addUserToList", "sendToList"})
     * @Assert\Length(
     *      max = 255
     * )
     * @Assert\NotNull
     *
     * @Groups({"read","write"})
     */
    private $action = 'getLists';

    /**
     * @var string A BS/SendList resource. Used for Adding a user as BS/Subscriber to a BS/SendList. And used for sending an email to all BS/SendList->Subscribers.
     *
     * @Groups({"read", "write"})
     * @Assert\Url
     * @Assert\Length(
     *     max=255
     * )
     */
    private $resource;

    /**
     * @var string The name of a new SendList. Used for creating a BS/SendList.
     *
     * @example News email
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $name;

    /**
     * @var string The description of a new SendList. Used for creating a BS/SendList.
     *
     * @example Mailing list for sending news
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $description;

    /**
     * @var bool True if this is a new mailing sendList. Used for creating a BS/SendList.
     *
     * @example true
     *
     * @Groups({"read", "write"})
     */
    private $mail = false;

    /**
     * @var bool True if this is a new phone sendList. Used for creating a BS/SendList.
     *
     * @example true
     *
     * @Groups({"read", "write"})
     */
    private $phone = false;

    /**
     * @var string The secret of your application. Used for creating a BS/SendList.
     *
     * @example e2984465-190a-4562-829e-a8cca81aa35d
     *
     * @Groups({"write"})
     */
    private $clientSecret;

    /**
     * @var string The title for sending an text/email to all BS/SendList->Subscribers.
     *
     * @example My awesome mailing
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $title;

    /**
     * @var string The message for sending an email to all BS/SendList->Subscribers.
     *
     * @example My mailing
     *
     * @Assert\Length(
     *      max = 2550
     * )
     * @Groups({"read","write"})
     */
    private $message;

    /**
     * @var string The text for sending an text to all BS/SendList->Subscribers.
     *
     * @example My mailing
     *
     * @Assert\Length(
     *      max = 255
     * )
     * @Groups({"read","write"})
     */
    private $text;

    /**
     * @var string The html for sending an email to all BS/SendList->Subscribers.
     *
     * @example <p>HTML content of the mail</p><p>{% if title is defined and title is not empty %}Title: {{ title }}{% endif %}</p><p>{% if message is defined and message is not empty %}Message: {{ message }}{% endif %}</p><p>{% if text is defined and text is not empty %}Text: {{ text }}{% endif %}</p><p>{% if resource.name is defined and resource.name is not empty %}(resource/)Sendlist name: {{ resource.name }}{% endif %}</p><p>{% if receiver.givenName is defined and receiver.givenName is not empty %}Receiver: {{ receiver.givenName }}{% endif %}</p><p>{% if sender.name is defined and sender.name is not empty %}Sender: {{ sender.name }}{% endif %}</p>
     *
     * @Assert\Length(
     *      max = 2550
     * )
     * @Groups({"read","write"})
     */
    private $html;

    /**
     * @var array The result
     *
     * @Groups({"read"})
     */
    private $result = [];

    /**
     * @var Datetime The moment this claim was created
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateCreated;

    /**
     * @var Datetime The moment this claim was last Modified
     *
     * @Groups({"read"})
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dateModified;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
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

    public function getMail(): ?bool
    {
        return $this->mail;
    }

    public function setMail(bool $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getPhone(): ?bool
    {
        return $this->phone;
    }

    public function setPhone(bool $phone): self
    {
        $this->phone = $phone;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(?string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function getResult(): ?array
    {
        return $this->result;
    }

    public function setResult(?array $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateModified(): ?\DateTimeInterface
    {
        return $this->dateModified;
    }

    public function setDateModified(\DateTimeInterface $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }
}
