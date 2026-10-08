<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;
use Phprise\KoenmaID\Doctrine\IdGenerator\PrefixedIdGenerator;
use Phprise\KoenmaID\Doctrine\Type\UserIdType;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\State\User\CreateProvider;
use Phprise\KoenmaID\State\User\UserCollectionProvider;
use Phprise\KoenmaID\State\User\UserDeleteProcessor;
use Phprise\KoenmaID\State\User\UserItemProvider;
use Phprise\KoenmaID\State\User\UserPatchProcessor;
use Phprise\KoenmaID\State\User\UserPostProcessor;
use Phprise\KoenmaID\ValueObject\ContractorId;
use Phprise\KoenmaID\ValueObject\UserId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'uniq_user_contractor_username', fields: ['contractor', 'username'])]
#[ORM\UniqueConstraint(name: 'uniq_user_contractor_email', fields: ['contractor', 'emailAddress'])]
#[ApiResource(
    description: 'A user belongs to a contractor and authenticates with username and password.',
    shortName: 'User',
    operations: [
        new GetCollection(
            openapi: new OpenApiOperation(summary: 'List users of a contractor', description: 'Returns every active user that belongs to the given contractor.'),
            uriTemplate: '/contractors/{contractorId}/users',
            uriVariables: ['contractorId' => new Link(fromClass: Contractor::class, toProperty: 'users')],
            provider: UserCollectionProvider::class,
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Get(
            openapi: new OpenApiOperation(summary: 'Get a user', description: 'Returns a single user by its identifier.'),
            uriTemplate: '/users/{id}',
            provider: UserItemProvider::class,
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Post(
            openapi: new OpenApiOperation(summary: 'Create a user', description: 'Registers a new user under the given contractor. Requires a valid security key of the owning partner or the master key.'),
            uriTemplate: '/contractors/{contractorId}/users',
            uriVariables: ['contractorId' => new Link(fromClass: Contractor::class, toProperty: 'users')],
            provider: CreateProvider::class,
            processor: UserPostProcessor::class,
            denormalizationContext: ['groups' => ['user:post']],
            validationContext: ['groups' => ['user:post']],
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Patch(
            openapi: new OpenApiOperation(summary: 'Update a user', description: 'Updates the username or the email address of a user.'),
            uriTemplate: '/users/{id}',
            provider: UserItemProvider::class,
            processor: UserPatchProcessor::class,
            denormalizationContext: ['groups' => ['user:patch']],
            validationContext: ['groups' => ['user:patch']],
            normalizationContext: ['groups' => ['user:get']],
        ),
        new Delete(
            openapi: new OpenApiOperation(summary: 'Delete a user', description: 'Soft-deletes a user. The request must carry the master key or the security key of the owning partner.'),
            uriTemplate: '/users/{id}',
            provider: UserItemProvider::class,
            processor: UserDeleteProcessor::class,
        ),
    ],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UserIdType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: PrefixedIdGenerator::class)]
    #[Groups(['user:get'])]
    private ?UserId $id = null;

    #[ORM\ManyToOne(targetEntity: Contractor::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(groups: ['user:post'])]
    private ?Contractor $contractor = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Email(groups: ['user:post', 'user:patch'])]
    #[Assert\Length(max: 255, groups: ['user:post', 'user:patch'])]
    #[Groups(['user:post', 'user:patch'])]
    private string $emailAddress;

    #[ORM\Column]
    #[Groups(['user:get'])]
    private bool $emailVerified = false;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(min: 3, max: 180, groups: ['user:post', 'user:patch'])]
    #[Groups(['user:post', 'user:patch'])]
    private string $username;

    #[ORM\Column(length: 255)]
    #[ApiProperty(initializable: true)]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(min: 8, max: 255, groups: ['user:post', 'user:patch'])]
    #[Groups(['user:post', 'user:patch'])]
    private string $password;

    #[ORM\Column]
    #[Groups(['user:get'])]
    private bool $active = true;

    #[ORM\Column]
    #[Groups(['user:get'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getId(): ?UserId
    {
        return $this->id;
    }

    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(Contractor $contractor): static
    {
        $this->contractor = $contractor;

        return $this;
    }

    #[Groups(['user:get'])]
    public function getContractorId(): ?ContractorId
    {
        return $this->contractor?->getId();
    }

    #[Groups(['user:get'])]
    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    #[Groups(['user:post', 'user:patch'])]
    public function setEmailAddress(string $emailAddress): static
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function changeEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
        $this->emailVerified = false;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function verifyEmail(): void
    {
        $this->emailVerified = true;
    }

    #[Groups(['user:get'])]
    public function getUsername(): string
    {
        return $this->username;
    }

    #[Groups(['user:post', 'user:patch'])]
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function rename(string $username): void
    {
        $this->username = $username;
    }

    #[Groups(['user:post', 'user:patch'])]
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function changePassword(string $passwordHash): void
    {
        $this->password = $passwordHash;
    }

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

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
