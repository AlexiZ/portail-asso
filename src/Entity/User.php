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

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /** @var string The hashed password */
    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\OneToMany(targetEntity: AssociationRevision::class, mappedBy: 'createdBy', cascade: ['persist'])]
    private Collection $revisions;

    public function __construct()
    {
        $this->revisions = new ArrayCollection();
    }

    public function getUsername(): string
    {
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

    public function setEmail(string $email): self
    {
        $this->email = $email;

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

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRevisions(): Collection
    {
        return $this->revisions;
    }

    public function setRevisions(Collection $revisions): User
    {
        $this->revisions = $revisions;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }
}
