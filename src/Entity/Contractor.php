<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\ContractorIdType;
use Phprise\KoenmaID\Repository\ContractorRepository;
use Phprise\KoenmaID\State\Contractor\ContractorCollectionProvider;
use Phprise\KoenmaID\State\Contractor\ContractorItemProvider;
use Phprise\KoenmaID\State\Contractor\ContractorPatchProcessor;
use Phprise\KoenmaID\State\Contractor\ContractorPostProcessor;
use Phprise\KoenmaID\State\Contractor\CreateProvider;
use Phprise\KoenmaID\ValueObject\ContractorId;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    description: 'A contractor belongs to a partner and groups the users that authenticate with username and password.',
    shortName: 'Contractor',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List contractors of a partner', description: 'Returns every contractor that belongs to the given partner.'),
            uriTemplate: '/partners/{partnerId}/contractors',
            uriVariables: ['partnerId' => new Link(fromClass: Partner::class, toProperty: 'partner')],
            provider: ContractorCollectionProvider::class,
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a contractor', description: 'Returns a single contractor by its identifier.'),
            uriTemplate: '/contractors/{id}',
            provider: ContractorItemProvider::class,
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a contractor', description: 'Registers a new contractor under the given partner.'),
            uriTemplate: '/partners/{partnerId}/contractors',
            uriVariables: ['partnerId' => new Link(fromClass: Partner::class, toProperty: 'partner')],
            provider: CreateProvider::class,
            processor: ContractorPostProcessor::class,
            denormalizationContext: ['groups' => ['contractor:post']],
            validationContext: ['groups' => ['contractor:post']],
            normalizationContext: ['groups' => ['contractor:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a contractor', description: 'Updates the name of a contractor.'),
            uriTemplate: '/contractors/{id}',
            provider: ContractorItemProvider::class,
            processor: ContractorPatchProcessor::class,
            denormalizationContext: ['groups' => ['contractor:patch']],
            validationContext: ['groups' => ['contractor:patch']],
            normalizationContext: ['groups' => ['contractor:get']],
        ),
    ],
)]
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
    #[Assert\NotNull(groups: ['contractor:post'])]
    private ?Partner $partner = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['contractor:post'])]
    #[Assert\Length(max: 255, groups: ['contractor:post', 'contractor:patch'])]
    #[Groups(['contractor:post', 'contractor:patch'])]
    private string $name;

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank(groups: ['contractor:post'])]
    #[Assert\Length(max: 32, groups: ['contractor:post'])]
    #[Groups(['contractor:post'])]
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

    public function __construct()
    {
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

    #[Groups(['contractor:get'])]
    public function getId(): ?ContractorId
    {
        return $this->id;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    #[Groups(['contractor:get'])]
    public function getPartnerId(): ?PartnerId
    {
        return $this->partner?->getId();
    }

    #[Groups(['contractor:get'])]
    public function getName(): string
    {
        return $this->name;
    }

    #[Groups(['contractor:patch'])]
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    #[Groups(['contractor:get'])]
    public function getDocument(): string
    {
        return $this->document;
    }

    #[Groups(['contractor:post'])]
    public function setDocument(string $document): static
    {
        $this->document = $document;

        return $this;
    }

    #[Groups(['contractor:get'])]
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

    #[Groups(['contractor:get'])]
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

    /** @return Collection<int, User> */
    public function users(): Collection
    {
        return $this->users;
    }
}
