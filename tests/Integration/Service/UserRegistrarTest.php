<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Integration\Service;

use Phprise\KoenmaID\ApiResource\User\UserInput;
use Phprise\KoenmaID\Entity\Contractor;
use Phprise\KoenmaID\Service\Security\SecurityKeyContext;
use Phprise\KoenmaID\Service\User\UserRegistrar;
use Phprise\KoenmaID\Tests\Factory\TestDataFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function testRegistersUserWithValidSecurityKey(): void
    {
        $contractor = $this->seedContractorWithKey('registrar-valid');
        $this->pushSecurityKey($this->securityKeyFor('registrar-valid'));

        $user = $this->registrar->register($contractor, $this->buildInput('newuser'));

        self::assertSame('newuser', $user->username());
        self::assertSame('newuser@example.com', $user->emailAddress());
        self::assertNotSame('plain-password', $user->getPassword());
    }

    public function testRejectsMissingSecurityKey(): void
    {
        $contractor = $this->seedContractorWithKey('registrar-missing');
        $this->pushSecurityKey(null);

        $this->expectException(UnauthorizedHttpException::class);

        $this->registrar->register($contractor, $this->buildInput('newuser'));
    }

    public function testRejectsSecurityKeyOfAnotherPartner(): void
    {
        $contractor = $this->seedContractorWithKey('registrar-own');
        $this->seedContractorWithKey('registrar-other');
        $this->pushSecurityKey($this->securityKeyFor('registrar-other'));

        $this->expectException(UnauthorizedHttpException::class);

        $this->registrar->register($contractor, $this->buildInput('newuser'));
    }

    public function testRejectsDuplicateUsernameInSameContractor(): void
    {
        $contractor = $this->seedContractorWithKey('registrar-dup');
        $this->pushSecurityKey($this->securityKeyFor('registrar-dup'));
        $this->factory->createUser($contractor, 'plain-password', 'dup');

        $this->expectException(ConflictHttpException::class);

        $this->registrar->register($contractor, $this->buildInput('userdup'));
    }

    private function seedContractorWithKey(string $suffix): Contractor
    {
        $partner = $this->factory->createPartner($suffix);
        $project = $this->factory->createProject($partner, $suffix);
        $this->factory->createApiKey($project, $this->securityKeyFor($suffix), $suffix);

        return $this->factory->createContractor($partner, $suffix);
    }

    private function securityKeyFor(string $suffix): string
    {
        return 'sk_'.str_pad(substr(md5($suffix), 0, 32), 32, '0');
    }

    private function pushSecurityKey(?string $securityKey): void
    {
        $stack = static::getContainer()->get('request_stack');
        while (null !== $stack->getCurrentRequest()) {
            $stack->pop();
        }

        $request = new Request();
        if (null !== $securityKey) {
            $request->headers->set(SecurityKeyContext::HEADER, $securityKey);
        }

        $stack->push($request);
    }

    private function buildInput(string $username): UserInput
    {
        $input = new UserInput();
        $input->username = $username;
        $input->emailAddress = $username.'@example.com';
        $input->password = 'plain-password';

        return $input;
    }
}
