<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /** @var string The hashed password */
    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var Collection<int, Subscription> */
    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'user')]
    private Collection $subscriptions;

    #[ORM\OneToMany(targetEntity: Association::class, mappedBy: 'owner', cascade: ['persist', 'remove'])]
    private Collection $chairedAssociations;

    /** @var Collection<int, Membership> */
    #[ORM\OneToMany(targetEntity: Membership::class, mappedBy: 'user')]
    private Collection $memberships;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'recipient', orphanRemoval: true)]
    private Collection $notifications;

    public function __construct()
    {
        $this->subscriptions = new ArrayCollection();
        $this->memberships = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getUsername(): string
    {
        if (!empty($this->firstname) && !empty($this->lastname)) {
            return $this->firstname.' '.$this->lastname;
        }

        $emailParts = explode('@', $this->email);

        return $emailParts[0];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_filter(array_unique($roles));
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function getHighestRole(): string
    {
        if (in_array('ROLE_ADMIN', $this->getRoles(), true)) {
            return 'ROLE_ADMIN';
        }
        if (in_array('ROLE_MODERATOR', $this->getRoles(), true)) {
            return 'ROLE_MODERATOR';
        }

        return 'ROLE_USER';
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @return Collection<int, Association>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Association $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
        }

        return $this;
    }

    public function removeSubscription(Association $subscription): static
    {
        $this->subscriptions->removeElement($subscription);

        return $this;
    }

    public function isSubscribedTo(Association $association): bool
    {
        return $this->subscriptions->contains($association);
    }

    public function getChairedAssociations(): Collection
    {
        return $this->chairedAssociations;
    }

    public function setChairedAssociations(Collection $chairedAssociations): static
    {
        $this->chairedAssociations = $chairedAssociations;

        return $this;
    }

    /**
     * @return Collection<int, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function getMembership(Association $association): ?Membership
    {
        foreach ($this->memberships as $membership) {
            if ($association === $membership->getAssociation()) {
                return $membership;
            }
        }

        return null;
    }

    public function addMembership(Membership $membership): static
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships->add($membership);
            $membership->setUser($this);
        }

        return $this;
    }

    public function removeMembership(Membership $membership): static
    {
        if ($this->memberships->removeElement($membership)) {
            if ($membership->getUser() === $this) {
                $membership->setUser(null);
            }
        }

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): static
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;

        return $this;
    }

    /** @return Collection<int, Notification> */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setRecipient($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getRecipient() === $this) {
                $notification->setRecipient(null);
            }
        }

        return $this;
    }
}
