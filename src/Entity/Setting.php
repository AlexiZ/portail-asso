<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'setting')]
#[ORM\UniqueConstraint(name: 'uniq_setting_key_name', columns: ['key_name'])]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'key_name', length: 100)]
    private string $key;

    #[ORM\Column(type: 'json')]
    private mixed $value;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $helpText;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isEditable = true;

    public function __construct(string $key = '', mixed $value = null, ?string $helpText = null)
    {
        $this->setKey($key);
        $this->setValue($value);
        $this->setHelpText($helpText);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        if (!$this->isEditable) {
            return $this;
        }

        $this->id = $id;

        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): static
    {
        if (!$this->isEditable) {
            return $this;
        }

        $this->key = $key;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): static
    {
        if (!$this->isEditable) {
            return $this;
        }

        $this->value = $value;

        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): static
    {
        if (!$this->isEditable) {
            return $this;
        }

        $this->helpText = $helpText;

        return $this;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function setIsEditable(bool $isEditable): Setting
    {
        if (!$this->isEditable) {
            return $this;
        }

        $this->isEditable = $isEditable;

        return $this;
    }
}
