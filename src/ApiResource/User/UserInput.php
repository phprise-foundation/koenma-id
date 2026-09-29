<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\ApiResource\User;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UserInput
{
    /** API Key of the project that owns the contractor. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(max: 255, groups: ['user:post'])]
    public string $apiKey = '';

    /** Name of the contractor. Used when the contractor does not exist yet. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(max: 255, groups: ['user:post'])]
    public string $contractorName = '';

    /** Document of the contractor. Used when the contractor does not exist yet. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(max: 32, groups: ['user:post'])]
    public string $contractorDocument = '';

    /** Username used to authenticate. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(min: 3, max: 180, groups: ['user:post'])]
    public string $username = '';

    /** Email address of the user. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Email(groups: ['user:post'])]
    #[Assert\Length(max: 255, groups: ['user:post'])]
    public string $emailAddress = '';

    /** Password of the user. */
    #[Groups(['user:post'])]
    #[Assert\NotBlank(groups: ['user:post'])]
    #[Assert\Length(min: 8, max: 255, groups: ['user:post'])]
    public string $password = '';
}
