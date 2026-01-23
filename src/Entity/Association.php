<?php

namespace App\Entity;

use App\Enum\AssociationCategory;
use App\Repository\AssociationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use RRule\RRule;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AssociationRepository::class)]
class Association
{
    use TimestampableEntity;
    use BlameableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['autocomplete'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['autocomplete'])]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    #[Groups(['autocomplete'])]
    #[Gedmo\Slug(fields: ['name'])]
    private string $slug;

    #[ORM\Column(type: Types::JSON, nullable: true, enumType: AssociationCategory::class)]
    #[Groups(['autocomplete'])]
    private ?array $categories = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['autocomplete'])]
    private ?string $logoFilename = null;

    /** @var Collection<int, Contact> */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'association', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $contacts;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $networkWebsite = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $networkFacebook = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $networkInstagram = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $networkOther = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\OneToMany(targetEntity: AssociationRevision::class, mappedBy: 'association', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $revisions;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'association', cascade: ['persist', 'remove'])]
    private Collection $events;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'chairedAssociations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    /** @var Collection<int, Subscription> */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'association')]
    private Collection $subscriptions;

    /** @var Collection<int, Membership> */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'association')]
    private Collection $memberships;

    #[ORM\Column(nullable: false, options: ['default' => true])]
    private bool $editablePageAnonymously = true;

    #[ORM\Column(nullable: false, options: ['default' => false])]
    private bool $editableEventsAnonymously = false;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->contacts = new ArrayCollection();
    }

    public function serialize(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'categories' => implode(',', $this->getCategoriesValues()),
            'logoFilename' => $this->logoFilename,
            'contacts' => implode(',', $this->getContactsValues()),
            'contactEmail' => $this->contactEmail,
            'contactPhone' => $this->contactPhone,
            'contactAddress' => $this->contactAddress,
            'networkWebsite' => $this->networkWebsite,
            'networkFacebook' => $this->networkFacebook,
            'networkInstagram' => $this->networkInstagram,
            'networkOther' => $this->networkOther,
            'content' => $this->content,
        ];
    }

    public function unserialize(array $data): self
    {
        $this->name = $data['name'];
        $this->slug = $data['slug'];
        $this->categories = array_map(
            fn (string $value) => AssociationCategory::from($value),
            explode(',', $data['categories']) ?? []
        );
        $this->logoFilename = $data['logoFilename'];
        $this->contacts = new ArrayCollection(array_map(function ($item) {
            [$function, $name] = explode('|', $item);
            $contact = new Contact();
            $contact->setFunction($function);
            $contact->setName($name);

            return $contact;
        }, explode(',', $data['contacts'])));
        $this->contactEmail = $data['contactEmail'];
        $this->contactPhone = $data['contactPhone'];
        $this->contactAddress = $data['contactAddress'];
        $this->networkWebsite = $data['networkWebsite'];
        $this->networkFacebook = $data['networkFacebook'];
        $this->networkInstagram = $data['networkInstagram'];
        $this->networkOther = $data['networkOther'];
        $this->content = $data['content'];

        return $this;
    }

    public function isWip(): bool
    {
        return $this->updatedBy = 'batch' && str_starts_with($this->updatedAt->format('Y-m-d H:i:s'), '2025-09-25 17:');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Association
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function getCategoriesValues(): ?array
    {
        $categories = [];
        /** @var AssociationCategory $category */
        foreach ($this->categories as $category) {
            $categories[] = $category->value;
        }

        return $categories;
    }

    public function setCategories(?array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    public function setLogoFilename(?string $logoFilename): self
    {
        $this->logoFilename = $logoFilename;

        return $this;
    }

    /** @return Collection<int, Contact> */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function getContactsValues(): ?array
    {
        $contacts = [];
        /** @var Contact $contact */
        foreach ($this->contacts as $contact) {
            $contacts[] = sprintf('%s|%s', $contact->getFunction(), $contact->getName());
        }

        return $contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setAssociation($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getAssociation() === $this) {
                $contact->setAssociation(null);
            }
        }

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getContactAddress(): ?string
    {
        return $this->contactAddress;
    }

    public function setContactAddress(?string $contactAddress): self
    {
        $this->contactAddress = $contactAddress;

        return $this;
    }

    public function hasAnyContactDetail(): bool
    {
        return $this->contacts->count() > 0 || !empty($this->contactEmail) || !empty($this->contactPhone) || !empty($this->contactAddress);
    }

    public function getNetworkWebsite(): ?string
    {
        return $this->networkWebsite;
    }

    public function setNetworkWebsite(?string $networkWebsite): self
    {
        $this->networkWebsite = $networkWebsite;

        return $this;
    }

    public function getNetworkFacebook(): ?string
    {
        return $this->networkFacebook;
    }

    public function setNetworkFacebook(?string $networkFacebook): self
    {
        $this->networkFacebook = $networkFacebook;

        return $this;
    }

    public function getNetworkInstagram(): ?string
    {
        return $this->networkInstagram;
    }

    public function setNetworkInstagram(?string $networkInstagram): self
    {
        $this->networkInstagram = $networkInstagram;

        return $this;
    }

    public function getNetworkOther(): ?string
    {
        return $this->networkOther;
    }

    public function setNetworkOther(?string $networkOther): self
    {
        $this->networkOther = $networkOther;

        return $this;
    }

    public function hasAnyNetwork(): bool
    {
        return !empty($this->networkWebsite) || !empty($this->networkFacebook) || !empty($this->networkInstagram) || !empty($this->networkOther);
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function setRevisions(Collection $revisions): self
    {
        $this->revisions = $revisions;

        return $this;
    }

    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function getPastEvents(): Collection
    {
        $now = new \DateTimeImmutable();

        return $this->events->filter(function (Event $event) use ($now) {
            return $event->getStartAt() < $now;
        });
    }

    public function getFutureEvents(): Collection
    {
        $now = new \DateTimeImmutable();
        $results = new ArrayCollection();

        foreach ($this->events as $event) {
            $startAt = $event->getStartAt();

            if ($event->getRecurrenceRule()) {
                $rule = new RRule($event->getRecurrenceRule());

                foreach ($rule->getOccurrencesAfter($now) as $date) {
                    $dateWithTime = (clone $date)->setTime(
                        (int) $startAt->format('H'),
                        (int) $startAt->format('i'),
                        (int) $startAt->format('s')
                    );

                    $occurrence = clone $event;
                    $occurrence->setStartAt($dateWithTime);

                    $results->add($occurrence);
                }
            } else {
                if ($startAt > $now) {
                    $results->add($event);
                }
            }
        }

        return $results;
    }

    public function setEvents(Collection $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscriber): static
    {
        if (!$this->subscriptions->contains($subscriber)) {
            $this->subscriptions->add($subscriber);
        }

        return $this;
    }

    public function removeSubscription(User $subscriber): static
    {
        if ($this->subscriptions->removeElement($subscriber)) {
            $subscriber->removeSubscription($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMembershipsByStatus(string $status): Collection
    {
        return new ArrayCollection(
            $this->memberships
                ->filter(fn ($membership) => $membership->getStatus() === $status)
                ->toArray()
        );
    }

    public function addMembership(Membership $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setAssociation($this);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): static
    {
        if ($this->memberships->removeElement($membership)) {
            // set the owning side to null (unless already changed)
            if ($membership->getAssociation() === $this) {
                $membership->setAssociation(null);
            }
        }

        return $this;
    }

    public function isEditablePageAnonymously(): bool
    {
        return $this->editablePageAnonymously;
    }

    public function setEditablePageAnonymously(bool $editablePageAnonymously): static
    {
        $this->editablePageAnonymously = $editablePageAnonymously;

        return $this;
    }

    public function isEditableEventsAnonymously(): bool
    {
        return $this->editableEventsAnonymously;
    }

    public function setEditableEventsAnonymously(bool $editableEventsAnonymously): static
    {
        $this->editableEventsAnonymously = $editableEventsAnonymously;

        return $this;
    }
}
