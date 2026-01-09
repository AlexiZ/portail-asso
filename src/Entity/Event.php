<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'events')]
    private Association $association;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Gedmo\Slug(fields: ['title'], unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text')]
    private string $shortDescription;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $posterFilename = null;

    #[ORM\Column(type: 'text')]
    private string $longDescription;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Event
    {
        $this->id = $id;

        return $this;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function setAssociation(Association $association): Event
    {
        $this->association = $association;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): Event
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): Event
    {
        $this->slug = $slug;

        return $this;
    }

    public function getShortDescription(): string
    {
        return str_replace('<p></p>', '', $this->shortDescription);
    }

    public function setShortDescription(string $shortDescription): Event
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

    public function setLongDescription(string $longDescription): Event
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    public function getStartAt(): \DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): Event
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt): Event
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): Event
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): Event
    {
        $this->isPublic = $isPublic;

        return $this;
    }
}
