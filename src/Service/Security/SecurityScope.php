<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\ValueObject\PartnerId;

final readonly class SecurityScope
{
    private function __construct(
        private bool $master,
        private ?Partner $partner,
    ) {
    }

    public static function master(): self
    {
        return new self(true, null);
    }

    public static function partner(Partner $partner): self
    {
        return new self(false, $partner);
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
        return !$this->master && null === $this->partner;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function partnerId(): ?PartnerId
    {
        return $this->partner?->getId();
    }
}
