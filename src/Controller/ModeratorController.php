<?php

namespace App\Controller;

use App\Repository\AssociationRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

#[Route('/moderation')]
class ModeratorController extends AbstractController
{
    #[Route('/', name: 'moderator_index')]
    public function index(AssociationRevisionRepository $revRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $pendingRevisions = $revRepo->findBy(['approved' => false], ['createdAt' => 'DESC']);

        return $this->render('moderator/index.html.twig', [
            'revisions' => $pendingRevisions,
        ]);
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
