<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Service\Security;

use Phprise\KoenmaID\Entity\Partner;
use Phprise\KoenmaID\Entity\Project;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MultiTenantAuthorizationVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    public function __construct(
        private readonly SecurityScope $securityScope,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Verifica se o atributo é um dos suportados
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        // Verifica se o subject é um objeto que pode ser verificado
        if (null === $subject) {
            return true; // Podemos verificar acesso global
        }

        // Verifica se o subject é uma entidade com relacionamento de partner
        return $subject instanceof Partner || $subject instanceof Project;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?\Symfony\Component\Security\Core\Authorization\Voter\Vote $vote = null): bool
    {
        $scope = $this->securityScope;
        
        // Se for acesso anônimo, nega
        if ($scope->isAnonymous()) {
            $this->throwAccessDeniedException();
        }
        
        // Se for master key, permite acesso total
        if ($scope->isMaster()) {
            return true;
        }
        
        $partner = $scope->getPartner();
        
        // Se for partner mas não tiver partner associado, nega acesso
        if (null === $partner) {
            $this->throwAccessDeniedException();
        }
        
        // Se o subject for null, permite acesso (acesso global para partner)
        if (null === $subject) {
            return true;
        }
        
        // Se for uma entidade Partner, verificar se é o próprio partner
        if ($subject instanceof Partner) {
            return $this->isSamePartner($subject, $partner);
        }
        
        // Se for uma entidade Project, verificar se pertence ao partner correto
        if ($subject instanceof Project) {
            return $this->isProjectInPartner($subject, $partner);
        }
        
        // Fallback para negar acesso
        return false;
    }
    
    private function isSamePartner(Partner $partner1, Partner $partner2): bool
    {
        return $partner1->getId()->equals($partner2->getId());
    }
    
    private function isProjectInPartner(Project $project, Partner $partner): bool
    {
        $projectPartner = $project->getPartner();
        
        // Se o projeto não tem partner associado, nega acesso
        if (null === $projectPartner) {
            return false;
        }
        
        return $this->isSamePartner($projectPartner, $partner);
    }
    
    private function throwAccessDeniedException(): void
    {
        // Podemos lançar uma exception do Symfony Security para que seja tratada corretamente
        throw new AccessDeniedException('Access denied.');
    }
}