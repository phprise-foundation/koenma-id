<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\ApiKeyIdType;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\State\ApiKey\ApiKeyCollectionProvider;
use Phprise\KoenmaID\State\ApiKey\ApiKeyDeleteProcessor;
use Phprise\KoenmaID\State\ApiKey\ApiKeyItemProvider;
use Phprise\KoenmaID\State\ApiKey\ApiKeyPatchProcessor;
use Phprise\KoenmaID\State\ApiKey\ApiKeyPostProcessor;
use Phprise\KoenmaID\State\ApiKey\CreateProvider;
use Phprise\KoenmaID\ValueObject\ApiKeyId;
use Phprise\KoenmaID\ValueObject\ProjectId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    description: 'An API Key authenticates a project in machine-to-machine calls. The plain key is shown only once, in the response of the create operation, and is never stored.',
    shortName: 'ApiKey',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List API Keys of a project', description: 'Returns every API Key that belongs to the given project. The plain key is never returned.'),
            uriTemplate: '/projects/{projectId}/api-keys',
            uriVariables: ['projectId' => new Link(fromClass: Project::class, toProperty: 'project')],
            provider: ApiKeyCollectionProvider::class,
            normalizationContext: ['groups' => ['api_key:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get an API Key', description: 'Returns a single API Key by its identifier. The plain key is never returned.'),
            uriTemplate: '/api-keys/{id}',
            provider: ApiKeyItemProvider::class,
            normalizationContext: ['groups' => ['api_key:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create an API Key', description: 'Issues a new API Key for the given project. The plain key is returned only in this response and cannot be recovered later.'),
            uriTemplate: '/projects/{projectId}/api-keys',
            uriVariables: ['projectId' => new Link(fromClass: Project::class, toProperty: 'project')],
            provider: CreateProvider::class,
            processor: ApiKeyPostProcessor::class,
            denormalizationContext: ['groups' => ['api_key:post']],
            validationContext: ['groups' => ['api_key:post']],
            normalizationContext: ['groups' => ['api_key:get', 'api_key:post']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update an API Key', description: 'Updates the name of an API Key.'),
            uriTemplate: '/api-keys/{id}',
            provider: ApiKeyItemProvider::class,
            processor: ApiKeyPatchProcessor::class,
            denormalizationContext: ['groups' => ['api_key:patch']],
            validationContext: ['groups' => ['api_key:patch']],
            normalizationContext: ['groups' => ['api_key:get']],
        ),
        new Delete(
            openapi: new OpenApiOperation(summary: 'Delete an API Key', description: 'Soft-deletes an API Key. The request must carry a valid security key of the partner that owns the project.'),
            uriTemplate: '/api-keys/{id}',
            provider: ApiKeyItemProvider::class,
            processor: ApiKeyDeleteProcessor::class,
        ),
    ],
)]
#[ORM\Entity(repositoryClass: ApiKeyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: ApiKeyIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    #[Groups(['api_key:get'])]
    private ?ApiKeyId $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $keyHash = '';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['api_key:post'])]
    #[Assert\Length(max: 255, groups: ['api_key:post', 'api_key:patch'])]
    #[Groups(['api_key:post', 'api_key:patch'])]
    private string $name = '';

    #[ORM\Column(length: 16)]
    #[Groups(['api_key:get'])]
    private string $keyPrefix = '';

    #[ORM\Column(length: 16)]
    #[Groups(['api_key:get'])]
    private string $keySuffix = '';

    #[ORM\Column(nullable: true)]
    #[Groups(['api_key:get'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    #[Groups(['api_key:get'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * Plain security key. Only included in the POST response.
     */
    #[Groups(['api_key:post'])]
    private ?string $securityKey = null;

    /**
     * Input-only: number of days until the key expires. Never persisted nor returned.
     */
    #[Assert\Positive(groups: ['api_key:post'])]
    private ?int $expiresInDays = null;

    public function __construct()
    {
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

    public function getId(): ?ApiKeyId
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    #[Groups(['api_key:get'])]
    public function getProjectId(): ?ProjectId
    {
        return $this->project?->getId();
    }

    public function getKeyHash(): string
    {
        return $this->keyHash;
    }

    public function setKeyHash(string $keyHash): static
    {
        $this->keyHash = $keyHash;

        return $this;
    }

    #[Groups(['api_key:get'])]
    public function getName(): string
    {
        return $this->name;
    }

    #[Groups(['api_key:patch'])]
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(['api_key:get'])]
    public function getKeyPrefix(): string
    {
        return $this->keyPrefix;
    }

    public function setKeyPrefix(string $keyPrefix): static
    {
        $this->keyPrefix = $keyPrefix;

        return $this;
    }

    #[Groups(['api_key:get'])]
    public function getKeySuffix(): string
    {
        return $this->keySuffix;
    }

    public function setKeySuffix(string $keySuffix): static
    {
        $this->keySuffix = $keySuffix;

        return $this;
    }

    #[Groups(['api_key:get'])]
    public function getExpiresAt(): ?\DateTimeImmutable
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

    #[Groups(['api_key:get'])]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    #[Groups(['api_key:post'])]
    public function setExpiresInDays(?int $expiresInDays): static
    {
        $this->expiresInDays = $expiresInDays;

        return $this;
    }

    public function getExpiresInDays(): ?int
    {
        return $this->expiresInDays;
    }

    #[Groups(['api_key:post'])]
    public function getSecurityKey(): ?string
    {
        return $this->securityKey;
    }

    public function setSecurityKey(string $securityKey): void
    {
        $this->securityKey = $securityKey;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }
}
