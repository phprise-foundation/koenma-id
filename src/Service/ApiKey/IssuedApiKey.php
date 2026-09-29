<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\ApiKey;

use Phprise\KoenmaID\Entity\ApiKey;

final readonly class IssuedApiKey
{
    public function __construct(
        public ApiKey $apiKey,
        public string $plainKey,
    ) {
    }
}
