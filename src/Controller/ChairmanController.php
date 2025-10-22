<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Membership;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/mes-associations')]
class ChairmanController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/', name: 'chairman_index')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isGranted('ROLE_ADMIN') && 0 === $user->getChairedAssociations()->count()) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('chairman/index.html.twig', [
            'associations' => $this->isGranted('ROLE_ADMIN') ? $this->entityManager->getRepository(Association::class)->findAllWithMemberships() : $user->getChairedAssociations(),
        ]);
    }

    #[Route('/{association}/accepter/{user}', name: 'chairman_accept_member')]
    #[IsGranted('manage', 'association')]
    public function addMember(
        #[MapEntity(mapping: ['association' => 'slug'])]
        Association $association,
        User $user,
    ): Response {
        $membership = $this->entityManager->getRepository(Membership::class)->findOneBy([
            'user' => $user,
            'association' => $association,
        ]);
        if (!$membership instanceof Membership) {
            throw $this->createNotFoundException();
        }

        $membership->setStatus(Membership::STATUS_ACCEPTED);

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('membership.accept.success', [
            'association' => $association->getName(),
            'user' => $user->getUsername(),
        ]));

        // @TODO : send mail to accepted user

        return $this->redirectToRoute('chairman_index');
    }

    #[Route('/{association}/refuser/{user}', name: 'chairman_refuse_member')]
    #[IsGranted('manage', 'association')]
    public function removeMember(
        #[MapEntity(mapping: ['association' => 'slug'])]
        Association $association,
        User $user,
    ): Response {
        $membership = $this->entityManager->getRepository(Membership::class)->findOneBy([
            'user' => $user,
            'association' => $association,
        ]);
        if (!$membership instanceof Membership) {
            throw $this->createNotFoundException();
        }

        $membership->setStatus(Membership::STATUS_REFUSED);

        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('membership.refuse.success', [
            'association' => $association->getName(),
            'user' => $user->getUsername(),
        ]));

        return $this->redirectToRoute('chairman_index');
    }
}
