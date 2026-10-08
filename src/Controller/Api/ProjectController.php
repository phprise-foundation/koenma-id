<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Controller\Api;

use Phprise\KoenmaID\Entity\ApiKey;
use Phprise\KoenmaID\Repository\ApiKeyRepository;
use Phprise\KoenmaID\Service\Security\ScopedProjectLookup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProjectController extends AbstractController
{
    public function __construct(
        private ScopedProjectLookup $scopedProjectLookup,
        private ApiKeyRepository $apiKeys,
    ) {
    }

    #[Route('/projects/{projectId}/api-keys', methods: ['GET'])]
    public function listApiKeys(string $projectId): Response
    {
        $project = $this->scopedProjectLookup->requireVisible($projectId);
        $apiKeys = $this->apiKeys->findActiveByProject($project);

        return $this->json($apiKeys);
    }
}