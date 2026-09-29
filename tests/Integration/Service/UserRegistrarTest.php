<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Service;

use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class UserRegistrarTest extends KernelTestCase
{
    private UserRegistrar $registrar;
    private TestDataFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->registrar = $container->get(UserRegistrar::class);
        $this->factory = $container->get(TestDataFactory::class);
    }

    public function testRegistersUserWithValidApiKey(): void
    {
        $partner = $this->factory->createPartner();
        $project = $this->factory->createProject($partner);
        $this->factory->createApiKey($project, 'valid-plain-key');

        $input = $this->buildInput('valid-plain-key', 'newuser');

        $user = $this->registrar->register($input);

        self::assertSame('newuser', $user->username());
        self::assertSame('newuser@example.com', $user->emailAddress());
        self::assertNotSame('plain-password', $user->getPassword());
    }

    public function testRejectsInvalidApiKey(): void
    {
        $input = $this->buildInput('unknown-key', 'newuser');

        $this->expectException(UnauthorizedHttpException::class);

        $this->registrar->register($input);
    }

    public function testRejectsDuplicateUsername(): void
    {
        $partner = $this->factory->createPartner();
        $project = $this->factory->createProject($partner);
        $this->factory->createApiKey($project, 'valid-plain-key');
        $contractor = $this->factory->createContractor($partner);
        $this->factory->createUser($contractor, 'plain-password', '1');

        $input = $this->buildInput('valid-plain-key', 'user1');

        $this->expectException(ConflictHttpException::class);

        $this->registrar->register($input);
    }

    private function buildInput(string $apiKey, string $username): UserInput
    {
        $input = new UserInput();
        $input->apiKey = $apiKey;
        $input->contractorName = 'Contractor X';
        $input->contractorDocument = 'doc-contractor-x';
        $input->username = $username;
        $input->emailAddress = $username.'@example.com';
        $input->password = 'plain-password';

        return $input;
    }
}
