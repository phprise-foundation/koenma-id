<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\User;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\Service\Security\MasterSecurityKey;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Phprise\KoenmaID\ValueObject\PartnerId;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrarTest extends TestCase
{
    private const string MASTER_KEY = 'sk_MMMMMMMMMMMMMMMMMMMMMMMMMMMMMMMM';
    private const string VALID_KEY = 'sk_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';

    public function testHashesPlainPasswordBeforePersisting(): void
    {
        $contractor = $this->contractorWithPartnerId(PartnerId::generate());
        $user = $this->newUser($contractor, 'newuser');

        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('findOneByContractorAndUsername')
            ->with($contractor, 'newuser')
            ->willReturn(null);

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'plain-password')
            ->willReturn('hashed-password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($user);
        $entityManager->expects($this->once())->method('flush');

        $registrar = new UserRegistrar(
            $users,
            $entityManager,
            $hasher,
            $this->securityKeyContext(self::MASTER_KEY),
        );

        $result = $registrar->register($user);

        self::assertSame($user, $result);
        self::assertSame('hashed-password', $result->getPassword());
        self::assertNotSame('plain-password', $result->getPassword());
    }

    public function testRejectsUsernameAlreadyRegisteredInSameContractor(): void
    {
        $contractor = $this->contractorWithPartnerId(PartnerId::generate());
        $user = $this->newUser($contractor, 'newuser');

        $users = $this->createMock(UserRepository::class);
        $users->expects($this->once())
            ->method('findOneByContractorAndUsername')
            ->with($contractor, 'newuser')
            ->willReturn($this->newUser($contractor, 'newuser'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $registrar = new UserRegistrar(
            $users,
            $entityManager,
            $this->createStub(UserPasswordHasherInterface::class),
            $this->securityKeyContext(self::MASTER_KEY),
        );

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Username already registered for this contractor.');

        $registrar->register($user);
    }

    public function testRejectsMissingSecurityKey(): void
    {
        $contractor = $this->contractorWithPartnerId(PartnerId::generate());
        $user = $this->newUser($contractor, 'newuser');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $registrar = new UserRegistrar(
            $this->createMock(UserRepository::class),
            $entityManager,
            $this->createStub(UserPasswordHasherInterface::class),
            $this->securityKeyContext(null),
        );

        $this->expectException(UnauthorizedHttpException::class);

        $registrar->register($user);
    }

    public function testRejectsSecurityKeyOfAnotherPartner(): void
    {
        $contractor = $this->contractorWithPartnerId(PartnerId::generate());
        $user = $this->newUser($contractor, 'newuser');

        $apiKeys = $this->createMock(ApiKeyRepository::class);
        $apiKeys->expects($this->once())
            ->method('findOneByHash')
            ->willReturn($this->apiKeyForPartner(PartnerId::generate()));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');

        $registrar = new UserRegistrar(
            $this->createMock(UserRepository::class),
            $entityManager,
            $this->createStub(UserPasswordHasherInterface::class),
            $this->securityKeyContext(self::VALID_KEY, $apiKeys),
        );

        $this->expectException(UnauthorizedHttpException::class);

        $registrar->register($user);
    }

    private function contractorWithPartnerId(PartnerId $partnerId): Contractor
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($partnerId);

        $contractor = $this->createStub(Contractor::class);
        $contractor->method('getPartner')->willReturn($partner);

        return $contractor;
    }

    private function apiKeyForPartner(PartnerId $partnerId): ApiKey
    {
        $partner = $this->createStub(Partner::class);
        $partner->method('getId')->willReturn($partnerId);

        $project = $this->createStub(Project::class);
        $project->method('getPartner')->willReturn($partner);

        $apiKey = $this->createStub(ApiKey::class);
        $apiKey->method('getProject')->willReturn($project);
        $apiKey->method('getDeletedAt')->willReturn(null);
        $apiKey->method('isExpired')->willReturn(false);

        return $apiKey;
    }

    private function newUser(Contractor $contractor, string $username): User
    {
        $user = (new User())->setContractor($contractor);
        $user->setUsername($username);
        $user->setEmailAddress($username.'@example.com');
        $user->setPassword('plain-password');

        return $user;
    }

    private function securityKeyContext(?string $header, ?ApiKeyRepository $apiKeys = null): SecurityKeyContext
    {
        $stack = new RequestStack();
        $request = new Request();

        if (null !== $header) {
            $request->headers->set(SecurityKeyContext::HEADER, $header);
        }

        $stack->push($request);

        return new SecurityKeyContext(
            $stack,
            $apiKeys ?? $this->createStub(ApiKeyRepository::class),
            new MasterSecurityKey(self::MASTER_KEY),
        );
    }
}
