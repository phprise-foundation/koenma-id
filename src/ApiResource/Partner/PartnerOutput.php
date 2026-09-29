<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Partner;

use Phprise\KoenmaID\Entity\Partner;
use Symfony\Component\Serializer\Attribute\Groups;

final class PartnerOutput
{
    /** Unique identifier of the partner. */
    #[Groups(['partner:get'])]
    public string $id = '';

    /** Legal name of the partner. */
    #[Groups(['partner:get'])]
    public string $name = '';

    /** Contact email address of the partner. */
    #[Groups(['partner:get'])]
    public string $emailAddress = '';

    /** Whether the email address was verified. */
    #[Groups(['partner:get'])]
    public bool $emailVerified = false;

    /** National registration document of the partner. */
    #[Groups(['partner:get'])]
    public string $document = '';

    /** Whether the partner is active. */
    #[Groups(['partner:get'])]
    public bool $active = true;

    /** Creation date in ISO 8601 format. */
    #[Groups(['partner:get'])]
    public string $createdAt = '';

    public static function fromEntity(Partner $partner): self
    {
        $output = new self();
        $output->id = (string) $partner->id();
        $output->name = $partner->name();
        $output->emailAddress = $partner->emailAddress();
        $output->emailVerified = $partner->emailVerified();
        $output->document = $partner->document();
        $output->active = $partner->active();
        $output->createdAt = $partner->createdAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
