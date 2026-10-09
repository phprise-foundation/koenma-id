<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class MasterScopeGuard
{
    public function __construct(private SecurityKeyContext $securityKeyContext)
    {
    }

    public function assertMaster(): void
    {
        $scope = $this->securityKeyContext->scope();

        if ($scope->isMaster()) {
            return;
        }

        if ($scope->isAnonymous()) {
            throw new UnauthorizedHttpException('SecurityKey', 'A master security key is required.');
        }

        throw new AccessDeniedHttpException('A master security key is required.');
    }
}
