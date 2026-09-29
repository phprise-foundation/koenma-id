<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\User;

use Phprise\KoenmaID\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;

final class UserOutput
{
    /** Unique identifier of the user. */
    #[Groups(['user:get'])]
    public string $id = '';

    /** Identifier of the contractor that owns the user. */
    #[Groups(['user:get'])]
    public string $contractorId = '';

    /** Username used to authenticate. */
    #[Groups(['user:get'])]
    public string $username = '';

    /** Email address of the user. */
    #[Groups(['user:get'])]
    public string $emailAddress = '';

    /** Whether the email address was verified. */
    #[Groups(['user:get'])]
    public bool $emailVerified = false;

    /** Whether the user is active. */
    #[Groups(['user:get'])]
    public bool $active = true;

    /** Creation date in ISO 8601 format. */
    #[Groups(['user:get'])]
    public string $createdAt = '';

    public static function fromEntity(User $user): self
    {
        $output = new self();
        $output->id = (string) $user->id();
        $output->contractorId = (string) $user->contractor()->id();
        $output->username = $user->username();
        $output->emailAddress = $user->emailAddress();
        $output->emailVerified = $user->emailVerified();
        $output->active = $user->active();
        $output->createdAt = $user->createdAt()->format(\DateTimeInterface::ATOM);

        return $output;
    }
}
