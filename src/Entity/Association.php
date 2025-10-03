<?php

namespace App\Entity;

use App\Enum\Association\Category;
use App\Repository\AssociationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AssociationRepository::class)]
class Association
{
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
    private string $slug;

    #[ORM\Column(type: Types::JSON, enumType: Category::class)]
    #[Groups(['autocomplete'])]
    private ?array $categories = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['autocomplete'])]
    private ?string $logoFilename = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $contactFunction = null;

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

    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    #[ORM\OneToMany(targetEntity: AssociationRevision::class, mappedBy: 'association', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $revisions;

    #[ORM\OneToMany(targetEntity: Event::class, mappedBy: 'association', cascade: ['persist', 'remove'])]
    private Collection $events;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $owner = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    private string $createdBy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['autocomplete'])]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
    #[Groups(['autocomplete'])]
    private string $updatedBy;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'subscriptions')]
    private Collection $subscribers;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->subscribers = new ArrayCollection();
    }

    public function serialize(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'categories' => implode(',', $this->getCategoriesValues()),
            'logoFilename' => $this->logoFilename,
            'contactName' => $this->contactName,
            'contactFunction' => $this->contactFunction,
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
            fn (string $value) => Category::from($value),
            explode(',', $data['categories']) ?? []
        );
        $this->logoFilename = $data['logoFilename'];
        $this->contactName = $data['contactName'];
        $this->contactFunction = $data['contactFunction'];
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
        /** @var Category $category */
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

    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    public function setContactName(?string $contactName): self
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactFunction(): ?string
    {
        return $this->contactFunction;
    }

    public function setContactFunction(?string $contactFunction): self
    {
        $this->contactFunction = $contactFunction;

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
        return !empty($this->contactName) || !empty($this->contactFunction) || !empty($this->contactEmail) || !empty($this->contactPhone) || !empty($this->contactAddress);
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

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
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

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    public function getUpdatedBy(): string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(User $subscriber): static
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers->add($subscriber);
            $subscriber->addSubscription($this);
        }

        return $this;
    }

    public function removeSubscriber(User $subscriber): static
    {
        if ($this->subscribers->removeElement($subscriber)) {
            $subscriber->removeSubscription($this);
        }

        return $this;
    }
}
