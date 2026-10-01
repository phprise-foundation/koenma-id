<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\ValueObject\PartnerId;

final readonly class SecurityScope
{
    private function __construct(
        private bool $master,
        private ?PartnerId $partnerId,
    ) {
    }

    public static function master(): self
    {
        return new self(true, null);
    }

    public static function partner(PartnerId $partnerId): self
    {
        return new self(false, $partnerId);
    }

    public static function anonymous(): self
    {
        return new self(false, null);
    }

    public function isMaster(): bool
    {
        return $this->master;
    }

    public function isAnonymous(): bool
    {
        return !$this->master && null === $this->partnerId;
    }

    public function partnerId(): ?PartnerId
    {
        return $this->partnerId;
    }
}
