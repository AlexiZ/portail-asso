<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'events')]
    private Association $association;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'], unique: true)]
    private string $slug;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'text')]
    private string $shortDescription;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $posterFilename = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'text')]
    private string $longDescription;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $recurrenceRule = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $location;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function setAssociation(Association $association): static
    {
        $this->association = $association;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getShortDescription(): string
    {
        return str_replace('<p></p>', '', $this->shortDescription);
    }

    public function setShortDescription(string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getPosterFilename(): ?string
    {
        return $this->posterFilename;
    }

    public function setPosterFilename(?string $posterFilename): static
    {
        $this->posterFilename = $posterFilename;

        return $this;
    }

    public function getLongDescription(): string
    {
        return str_replace('<p></p>', '', $this->longDescription);
    }

    public function setLongDescription(string $longDescription): static
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    public function getStartAt(): \DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getRecurrenceRule(): ?string
    {
        return $this->recurrenceRule;
    }

    public function setRecurrenceRule(?string $recurrenceRule): static
    {
        $this->recurrenceRule = $recurrenceRule;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }
}
