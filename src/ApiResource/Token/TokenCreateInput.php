<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenCreateInput
{
    /** API Key of the project that owns the user. */
    #[Groups(['token:create'])]
    #[Assert\NotBlank(groups: ['token:create'])]
    #[Assert\Length(max: 255, groups: ['token:create'])]
    public string $apiKey = '';

    /** Username of the user that is starting the session. */
    #[Groups(['token:create'])]
    #[Assert\NotBlank(groups: ['token:create'])]
    #[Assert\Length(max: 180, groups: ['token:create'])]
    public string $username = '';

    /** Password of the user. */
    #[Groups(['token:create'])]
    #[Assert\NotBlank(groups: ['token:create'])]
    #[Assert\Length(max: 255, groups: ['token:create'])]
    public string $password = '';
}
