<?php

namespace App\Controller;

use App\Enum\Association\Category;
use App\Repository\AssociationRepository;
use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(EventRepository $eventRepo, AssociationRepository $assocRepo): Response
    {
        $events = $eventRepo->createQueryBuilder('e')
            ->andWhere('e.startAt >= :now')
            ->andWhere('e.startAt <= :limit')
            ->andWhere('e.isPublic = true')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('limit', (new \DateTimeImmutable())->modify('+30 days'))
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $associations = $assocRepo->createQueryBuilder('a')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('page/home.html.twig', [
            'events' => $events,
            'associations' => $associations,
            'agendaNbDays' => 30,
            'categories' => Category::cases(),
        ]);
    }

    #[Route('/charte-d-edition', name: 'publishing_charter')]
    public function charter(): Response
    {
        return $this->render('page/charter.html.twig');
    }
}
