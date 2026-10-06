<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

enum SecurityKeyType
{
    case Master;
    case Partner;
    case Anonymous;
}
