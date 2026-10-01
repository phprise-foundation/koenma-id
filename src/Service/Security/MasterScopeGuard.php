<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class MasterScopeGuard
{
    public function __construct(private SecurityKeyContext $securityKeyContext)
    {
    }

    public function assertMaster(): void
    {
        if ($this->securityKeyContext->scope()->isMaster()) {
            return;
        }

        throw new UnauthorizedHttpException('SecurityKey', 'A master security key is required.');
    }
}
