<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Contractor;

use Phprise\KoenmaID\Entity\Contractor;
use Symfony\Component\Serializer\Attribute\Groups;

final class ContractorOutput
{
    /** Unique identifier of the contractor. */
    #[Groups(['contractor:get'])]
    public string $id = '';

    /** Identifier of the partner that owns the contractor. */
    #[Groups(['contractor:get'])]
    public string $partnerId = '';

    /** Legal name of the contractor. */
    #[Groups(['contractor:get'])]
    public string $name = '';

    /** National registration document of the contractor. */
    #[Groups(['contractor:get'])]
    public string $document = '';

    /** Whether the contractor is active. */
    #[Groups(['contractor:get'])]
    public bool $active = true;

    /** Creation date in ISO 8601 format. */
    #[Groups(['contractor:get'])]
    public string $createdAt = '';

    public static function fromEntity(Contractor $contractor): self
    {
        $output = new self();
        $output->id = (string) $contractor->id();
        $output->partnerId = (string) $contractor->partner()->id();
        $output->name = $contractor->name();
        $output->document = $contractor->document();
        $output->active = $contractor->active();
        $output->createdAt = $contractor->createdAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
