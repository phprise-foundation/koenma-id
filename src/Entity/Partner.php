<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\PartnerIdType;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: PartnerIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    private ?PartnerId $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 255)]
    private string $emailAddress;

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $document;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, Project> */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'partner')]
    private Collection $projects;

    /** @var Collection<int, Contractor> */
    #[ORM\OneToMany(targetEntity: Contractor::class, mappedBy: 'partner')]
    private Collection $contractors;

    public function __construct(string $name, string $emailAddress, string $document)
    {
        $this->name = $name;
        $this->emailAddress = $emailAddress;
        $this->document = $document;
        $this->projects = new ArrayCollection();
        $this->contractors = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): ?PartnerId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function emailAddress(): string
    {
        return $this->emailAddress;
    }

    public function changeEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
        $this->emailVerified = false;
    }

    public function emailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
    }

    public function document(): string
    {
        return $this->document;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function activate(): void
    {
        $this->active = true;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, Project> */
    public function projects(): Collection
    {
        return $this->projects;
    }

    /** @return Collection<int, Contractor> */
    public function contractors(): Collection
    {
        return $this->contractors;
    }
}
