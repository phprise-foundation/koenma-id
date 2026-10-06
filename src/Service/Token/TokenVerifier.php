<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Token;

use Phprise\KoenmaID\Entity\User;
use Phprise\KoenmaID\Repository\UserRepository;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final readonly class TokenVerifier
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $users,
    ) {
    }

    public function verify(string $token, SecurityScope $scope): VerifiedToken
    {
        try {
            $payload = $this->jwtManager->parse($token);
        } catch (JWTDecodeFailureException) {
            return VerifiedToken::invalid();
        }

        $username = (string) ($payload['username'] ?? '');

        if (!$this->belongsToScope($username, $scope)) {
            return VerifiedToken::invalid();
        }

        return VerifiedToken::valid($username, (int) ($payload['exp'] ?? 0));
    }

    private function belongsToScope(string $username, SecurityScope $scope): bool
    {
        if ($scope->isMaster()) {
            return true;
        }

        $user = $this->users->findOneByUsername($username);

        if (!$user instanceof User) {
            return false;
        }

        $userPartnerId = $user->getContractor()->getPartner()->getId();

        return null !== $scope->partnerId() && $scope->partnerId()->equals($userPartnerId);
    }
}
