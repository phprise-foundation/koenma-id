<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class MasterSecurityKey
{
    public function __construct(
        #[Autowire('%env(MASTER_SECURITY_KEY)%')]
        private string $value,
    ) {
    }

    public function matches(string $candidate): bool
    {
        if ('' === $this->value) {
            return false;
        }

        return hash_equals($this->value, $candidate);
    }
}
