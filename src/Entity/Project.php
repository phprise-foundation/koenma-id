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
use Phprise\KoenmaID\Doctrine\Type\ProjectIdType;
use Phprise\KoenmaID\Repository\ProjectRepository;
use Phprise\KoenmaID\State\Project\CreateProvider;
use Phprise\KoenmaID\State\Project\ProjectCollectionProvider;
use Phprise\KoenmaID\State\Project\ProjectDeleteProcessor;
use Phprise\KoenmaID\State\Project\ProjectItemProvider;
use Phprise\KoenmaID\State\Project\ProjectPatchProcessor;
use Phprise\KoenmaID\State\Project\ProjectPostProcessor;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Phprise\KoenmaID\ValueObject\ProjectId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    description: 'A project belongs to a partner and groups the API Keys used to authenticate machine-to-machine calls.',
    shortName: 'Project',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List projects of a partner', description: 'Returns every project that belongs to the given partner.'),
            uriTemplate: '/partners/{partnerId}/projects',
            uriVariables: ['partnerId' => new Link(fromClass: Partner::class, toProperty: 'partner')],
            provider: ProjectCollectionProvider::class,
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a project', description: 'Returns a single project by its identifier.'),
            uriTemplate: '/projects/{id}',
            provider: ProjectItemProvider::class,
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a project', description: 'Registers a new project under the given partner.'),
            uriTemplate: '/partners/{partnerId}/projects',
            uriVariables: ['partnerId' => new Link(fromClass: Partner::class, toProperty: 'partner')],
            provider: CreateProvider::class,
            processor: ProjectPostProcessor::class,
            denormalizationContext: ['groups' => ['project:post']],
            validationContext: ['groups' => ['project:post']],
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a project', description: 'Updates the name or the description of a project.'),
            uriTemplate: '/projects/{id}',
            provider: ProjectItemProvider::class,
            processor: ProjectPatchProcessor::class,
            denormalizationContext: ['groups' => ['project:patch']],
            validationContext: ['groups' => ['project:patch']],
            normalizationContext: ['groups' => ['project:get']],
        ),
        new Delete(
            openapi: new OpenApiOperation(summary: 'Delete a project', description: 'Soft-deletes a project. The request must carry the master key or the security key of the owning partner.'),
            uriTemplate: '/projects/{id}',
            provider: ProjectItemProvider::class,
            processor: ProjectDeleteProcessor::class,
        ),
    ],
)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: ProjectIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    private ?ProjectId $id = null;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Partner $partner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['project:post'])]
    #[Assert\Length(max: 255, groups: ['project:post', 'project:patch'])]
    #[Groups(['project:post', 'project:patch'])]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000, groups: ['project:post', 'project:patch'])]
    #[Groups(['project:post', 'project:patch'])]
    private ?string $description = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /** @var Collection<int, ApiKey> */
    #[ORM\OneToMany(targetEntity: ApiKey::class, mappedBy: 'project')]
    private Collection $apiKeys;

    public function __construct()
    {
        $this->apiKeys = new ArrayCollection();
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

    #[Groups(['project:get'])]
    public function getId(): ?ProjectId
    {
        return $this->id;
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function setPartner(Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    #[Groups(['project:get'])]
    public function getPartnerId(): ?PartnerId
    {
        return $this->partner?->getId();
    }

    #[Groups(['project:get'])]
    public function getName(): string
    {
        return $this->name;
    }

    #[Groups(['project:patch'])]
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(['project:get'])]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Groups(['project:patch'])]
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(['project:get'])]
    public function isActive(): bool
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

    #[Groups(['project:get'])]
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function delete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, ApiKey> */
    public function apiKeys(): Collection
    {
        return $this->apiKeys;
    }
}
