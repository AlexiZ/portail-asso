<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AssociationRevision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'revisions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Association $association;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private string $createdBy;

    #[ORM\Column(type: 'text')]
    private string $contentBefore;

    #[ORM\Column(type: 'text')]
    private string $contentAfter;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $approved = false; // moderators will approve

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function isApproved(): bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): self
    {
        $this->approved = $approved;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContentAfter(): string
    {
        return $this->contentAfter;
    }

    public function setContentAfter(string $contentAfter): self
    {
        $this->contentAfter = $contentAfter;

        return $this;
    }

    public function getContentBefore(): string
    {
        return $this->contentBefore;
    }

    public function setContentBefore(string $contentBefore): self
    {
        $this->contentBefore = $contentBefore;

        return $this;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getAssociation(): Association
    {
        return $this->association;
    }

    public function setAssociation(Association $association): self
    {
        $this->association = $association;

        return $this;
    }
}
