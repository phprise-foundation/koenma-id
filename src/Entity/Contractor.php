<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\ContractorIdType;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\ValueObject\ContractorId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractorRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contractor
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: ContractorIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    private ?ContractorId $id = null;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'contractors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private Partner $partner;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

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

    /** @var Collection<int, User> */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'contractor')]
    private Collection $users;

    public function __construct(Partner $partner, string $name, string $document)
    {
        $this->partner = $partner;
        $this->name = $name;
        $this->document = $document;
        $this->users = new ArrayCollection();
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

    public function id(): ?ContractorId
    {
        return $this->id;
    }

    public function partner(): Partner
    {
        return $this->partner;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
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

    /** @return Collection<int, User> */
    public function users(): Collection
    {
        return $this->users;
    }
}
