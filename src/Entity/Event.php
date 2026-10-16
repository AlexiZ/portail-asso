<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use RRule\RRule;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
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

    #[Assert\GreaterThan(propertyPath: 'startAt')]
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isPublic = true;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $recurrenceRule = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    /** Start of the source event, kept on occurrences built by withStartDate() to anchor the recurrence rule */
    private ?\DateTimeInterface $sourceStartAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

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

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(?\DateTimeInterface $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * End date, or end of the start day when not set (never equal to the start date).
     */
    public function getEffectiveEndAt(): \DateTimeInterface
    {
        if ($this->endAt) {
            return $this->endAt;
        }

        $endOfDay = \DateTimeImmutable::createFromInterface($this->startAt)->setTime(23, 59, 59);

        return $endOfDay > $this->startAt ? $endOfDay : $endOfDay->modify('+1 minute');
    }

    /**
     * Copy of this event starting on $day at the same time, its end shifted accordingly.
     */
    public function withStartDate(\DateTimeInterface $day): static
    {
        $startAt = \DateTime::createFromInterface($day)->setTime(
            (int) $this->startAt->format('H'),
            (int) $this->startAt->format('i'),
            (int) $this->startAt->format('s'),
        );

        $occurrence = clone $this;
        $occurrence->sourceStartAt = $this->sourceStartAt ?? $this->startAt;
        $occurrence->startAt = $startAt;
        if ($this->endAt) {
            $occurrence->endAt = (clone $startAt)->add($this->startAt->diff($this->endAt));
        }

        return $occurrence;
    }

    /**
     * Recurrence rule anchored on startAt: rules saved by the form have no DTSTART,
     * and RRule would otherwise start from "now".
     */
    public function getRRule(): ?RRule
    {
        if (!$this->recurrenceRule) {
            return null;
        }

        return str_contains($this->recurrenceRule, 'DTSTART')
            ? new RRule($this->recurrenceRule)
            : new RRule($this->recurrenceRule, \DateTime::createFromInterface($this->sourceStartAt ?? $this->startAt));
    }

    /**
     * Date of the first occurrence of the series, or null if the event doesn't recur.
     */
    public function getFirstOccurrenceAt(): ?\DateTimeInterface
    {
        return $this->getRRule()?->getOccurrences(1)[0] ?? null;
    }

    /**
     * The occurrence of this event taking place on $day, or null if none.
     */
    public function occurrenceOn(\DateTimeImmutable $day): ?static
    {
        if (!$this->recurrenceRule) {
            return $day->format('Y-m-d') === $this->startAt->format('Y-m-d') ? $this : null;
        }

        return $this->getRRule()->getOccurrencesBetween($day->setTime(0, 0), $day->setTime(23, 59, 59), 1) ? $this->withStartDate($day) : null;
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }
}
