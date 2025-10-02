<?php

namespace App\Controller;

use App\Entity\Association;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/recherche', name: 'search')]
    public function charter(Request $request): Response
    {
        $searchTerm = $request->query->get('search');
        $associations = $this->entityManager->getRepository(Association::class)->searchByName($searchTerm);

        return $this->render('search/index.html.twig', [
            'searchTerm' => $searchTerm,
            'associations' => $associations,
        ]);
    }
}
