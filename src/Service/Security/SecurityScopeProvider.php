<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

interface SecurityScopeProvider
{
    public function scope(): SecurityScope;
}
