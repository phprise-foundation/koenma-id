<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\ApiKeyIdType;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\ValueObject\ApiKeyId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: ApiKeyIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    private ?ApiKeyId $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private Project $project;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $keyHash;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $keyPrefix;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $keySuffix;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(Project $project, string $name, string $keyHash, string $keyPrefix, string $keySuffix)
    {
        $this->project = $project;
        $this->name = $name;
        $this->keyHash = $keyHash;
        $this->keyPrefix = $keyPrefix;
        $this->keySuffix = $keySuffix;
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

    public function id(): ?ApiKeyId
    {
        return $this->id;
    }

    public function project(): Project
    {
        return $this->project;
    }

    public function keyHash(): string
    {
        return $this->keyHash;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function keyPrefix(): string
    {
        return $this->keyPrefix;
    }

    public function keySuffix(): string
    {
        return $this->keySuffix;
    }

    public function expiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function expireAt(?\DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function isExpired(): bool
    {
        return null !== $this->expiresAt && $this->expiresAt < new \DateTimeImmutable();
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
}
