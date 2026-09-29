<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\Token;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class TokenVerifyInput
{
    /** Access token to inspect. */
    #[Groups(['token:verify'])]
    #[Assert\NotBlank(groups: ['token:verify'])]
    #[Assert\Length(max: 4096, groups: ['token:verify'])]
    public string $token = '';
}
