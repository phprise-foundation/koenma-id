<?php

declare(strict_types=1);

namespace Phprise\KoenmaID\Controller\Api;

use Phprise\KoenmaID\Service\Security\ScopedPartnerLookup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PartnerController extends AbstractController
{
    public function __construct(
        private ScopedPartnerLookup $scopedPartnerLookup,
    ) {
    }

    #[Route('/partners', methods: ['GET'])]
    public function list(): Response
    {
        $partners = $this->scopedPartnerLookup->visible();
        
        return $this->json($partners);
    }
}