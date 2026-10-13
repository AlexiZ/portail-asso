<?php

namespace App\Controller;

use App\Repository\AssociationRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/moderation')]
class ModeratorController extends AbstractController
{
    private const PER_PAGE = 20;

    #[Route('/', name: 'moderator_index')]
    public function index(Request $request, AssociationRevisionRepository $revRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $filters = [
            'association' => $request->query->getInt('association') ?: null,
            'from' => $this->parseDate($request->query->getString('from')),
            'to' => $this->parseDate($request->query->getString('to'))?->modify('+1 day'),
            'q' => trim($request->query->getString('q')) ?: null,
        ];
        $page = max(1, $request->query->getInt('page', 1));

        $revisions = $revRepo->findPendingPaginated($filters['association'], $filters['from'], $filters['to'], $filters['q'], $page, self::PER_PAGE);

        $params = [
            'revisions' => $revisions,
            'page' => $page,
            'pages' => max(1, (int) ceil(\count($revisions) / self::PER_PAGE)),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('moderator/_revisions.html.twig', $params);
        }

        return $this->render('moderator/index.html.twig', $params + [
            'associations' => $revRepo->findAssociationsWithPending(),
        ]);
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('!Y-m-d', $value) ?: null;
    }

    #[Route('/revision/{id}/approve', name: 'moderator_approve_revision', methods: ['POST'])]
    #[IsCsrfTokenValid('moderator_approve_revision')]
    public function approve($id, AssociationRevisionRepository $revRepo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $revision = $revRepo->find($id);
        if ($revision) {
            $revision->setApproved(true);
            $em->flush();
            $this->addFlash('success', 'Modification approuvée.');
        } else {
            $this->addFlash('danger', 'Révision introuvable.');
        }

        return $this->redirectToRoute('moderator_index');
    }
}
