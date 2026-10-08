<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Tests\Unit\Service\Security;

use Phprise\KoenmaID\Service\Security\MultiTenantAuthorizationVoter;
use Phprise\KoenmaID\Service\Security\SecurityScope;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class MultiTenantAuthorizationVoterTest extends TestCase
{
    public function testSupports(): void
    {
        // Apenas testando a lógica de suporte dos atributos
        $scope = SecurityScope::anonymous();
        $voter = new MultiTenantAuthorizationVoter($scope);
        
        // Testa atributos suportados
        $this->assertTrue($voter->supports(MultiTenantAuthorizationVoter::VIEW, null));
        $this->assertTrue($voter->supports(MultiTenantAuthorizationVoter::EDIT, null));
        $this->assertTrue($voter->supports(MultiTenantAuthorizationVoter::DELETE, null));
        
        // Testa atributos não suportados
        $this->assertFalse($voter->supports('UNKNOWN', null));
        
        // Testa com entidades - as classes são finais, então apenas testamos a lógica
        $this->assertTrue($voter->supports(MultiTenantAuthorizationVoter::VIEW, new \stdClass()));
    }
    
    public function testVoteOnAttributeWithAnonymousScope(): void
    {
        // Criando com escopo anônimo diretamente
        $scope = SecurityScope::anonymous();
        
        $voter = new MultiTenantAuthorizationVoter($scope);
        $token = $this->createMock(TokenInterface::class);
        
        $this->expectException(AccessDeniedException::class);
        $voter->voteOnAttribute(MultiTenantAuthorizationVoter::VIEW, null, $token);
    }
    
    public function testVoteOnAttributeWithMasterScope(): void
    {
        // Criando com escopo master diretamente
        $scope = SecurityScope::master();
        
        $voter = new MultiTenantAuthorizationVoter($scope);
        $token = $this->createMock(TokenInterface::class);
        
        $this->assertTrue($voter->voteOnAttribute(MultiTenantAuthorizationVoter::VIEW, null, $token));
    }
    
    public function testVoteOnAttributeWithPartnerScope(): void
    {
        // Criando com escopo partner diretamente
        $scope = SecurityScope::partner($this->createMock(\Phprise\KoenmaID\Entity\Partner::class));
        
        $voter = new MultiTenantAuthorizationVoter($scope);
        $token = $this->createMock(TokenInterface::class);
        
        $this->assertTrue($voter->voteOnAttribute(MultiTenantAuthorizationVoter::VIEW, null, $token));
    }
}