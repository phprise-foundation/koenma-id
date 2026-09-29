<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\User;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UserPatchInput
{
    /** New username used to authenticate. */
    #[Groups(['user:patch'])]
    #[Assert\Length(min: 3, max: 180, groups: ['user:patch'])]
    public ?string $username = null;

    /** New email address of the user. */
    #[Groups(['user:patch'])]
    #[Assert\Email(groups: ['user:patch'])]
    #[Assert\Length(max: 255, groups: ['user:patch'])]
    public ?string $emailAddress = null;

    /** New password of the user. */
    #[Groups(['user:patch'])]
    #[Assert\Length(min: 8, max: 255, groups: ['user:patch'])]
    public ?string $password = null;
}
