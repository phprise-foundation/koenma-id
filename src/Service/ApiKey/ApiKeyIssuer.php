<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\ApiKey;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ApiKeyIssuer
{
    private const int PREFIX_LENGTH = 8;
    private const int SUFFIX_LENGTH = 8;
    private const int SECRET_LENGTH = 32;
    private const string SECRET_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    private const string SECRET_PREFIX = 'sk_';

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function issue(Project $project, string $name, ?int $expiresInDays): IssuedApiKey
    {
        $plainKey = $this->generatePlainKey();
        $expiresAt = $this->resolveExpiration($expiresInDays);

        $apiKey = new ApiKey(
            $project,
            $name,
            $this->hash($plainKey),
            substr($plainKey, 0, self::PREFIX_LENGTH),
            substr($plainKey, -self::SUFFIX_LENGTH),
        );
        $apiKey->expireAt($expiresAt);

        $this->entityManager->persist($apiKey);
        $this->entityManager->flush();

        return new IssuedApiKey($apiKey, $plainKey);
    }

    private function generatePlainKey(): string
    {
        return self::SECRET_PREFIX.$this->generateSecret();
    }

    private function generateSecret(): string
    {
        $secret = '';
        $maxIndex = \strlen(self::SECRET_ALPHABET) - 1;

        for ($position = 0; $position < self::SECRET_LENGTH; ++$position) {
            $secret .= self::SECRET_ALPHABET[random_int(0, $maxIndex)];
        }

        return $secret;
    }

    private function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    private function resolveExpiration(?int $expiresInDays): ?\DateTimeImmutable
    {
        if (null === $expiresInDays) {
            return null;
        }

        return new \DateTimeImmutable(\sprintf('+%d days', $expiresInDays));
    }
}
