<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\PartnerIdType;
use Phprise\KoenmaID\Repository\PartnerRepository;
use Phprise\KoenmaID\State\Partner\PartnerCollectionProvider;
use Phprise\KoenmaID\State\Partner\PartnerDeleteProcessor;
use Phprise\KoenmaID\State\Partner\PartnerItemProvider;
use Phprise\KoenmaID\State\Partner\PartnerPatchProcessor;
use Phprise\KoenmaID\State\Partner\PartnerPostProcessor;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    description: 'A partner is the organization that contracts the API. It owns projects and contractors.',
    shortName: 'Partner',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List partners', description: 'Returns every partner.'),
            uriTemplate: '/partners',
            provider: PartnerCollectionProvider::class,
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a partner', description: 'Returns a single partner by its identifier.'),
            uriTemplate: '/partners/{id}',
            provider: PartnerItemProvider::class,
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a partner', description: 'Registers a new partner.'),
            uriTemplate: '/partners',
            processor: PartnerPostProcessor::class,
            denormalizationContext: ['groups' => ['partner:post']],
            validationContext: ['groups' => ['partner:post']],
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a partner', description: 'Updates the name or the email address of a partner. Requires the master security key.'),
            uriTemplate: '/partners/{id}',
            provider: PartnerItemProvider::class,
            processor: PartnerPatchProcessor::class,
            denormalizationContext: ['groups' => ['partner:patch']],
            validationContext: ['groups' => ['partner:patch']],
            normalizationContext: ['groups' => ['partner:get']],
        ),
        new Delete(
            openapi: new OpenApiOperation(summary: 'Delete a partner', description: 'Soft-deletes a partner. The request must carry the master key or the security key of the partner itself.'),
            uriTemplate: '/partners/{id}',
            provider: PartnerItemProvider::class,
            processor: PartnerDeleteProcessor::class,
        ),
    ],
)]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: PartnerIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    private ?PartnerId $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Length(max: 255, groups: ['partner:post', 'partner:patch'])]
    #[Groups(['partner:post', 'partner:patch'])]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Email(groups: ['partner:post', 'partner:patch'])]
    #[Assert\Length(max: 255, groups: ['partner:post', 'partner:patch'])]
    #[Groups(['partner:post', 'partner:patch'])]
    private string $emailAddress;

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column(length: 32, unique: true)]
    #[Assert\NotBlank(groups: ['partner:post'])]
    #[Assert\Length(max: 32, groups: ['partner:post'])]
    #[Groups(['partner:post'])]
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

    #[Groups(['partner:get'])]
    public function getId(): ?PartnerId
    {
        return $this->id;
    }

    #[Groups(['partner:get'])]
    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    #[Groups(['partner:get'])]
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function changeEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
        $this->emailVerified = false;
    }

    #[Groups(['partner:patch'])]
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    #[Groups(['partner:patch'])]
    public function setEmailAddress(string $emailAddress): void
    {
        $this->changeEmailAddress($emailAddress);
    }

    #[Groups(['partner:get'])]
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
    }

    #[Groups(['partner:get'])]
    public function getDocument(): string
    {
        return $this->document;
    }

    #[Groups(['partner:get'])]
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

    #[Groups(['partner:get'])]
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
