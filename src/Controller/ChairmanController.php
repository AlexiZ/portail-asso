<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Membership;
use App\Entity\User;
use App\Factory\AssociationFactory;
use App\Factory\MembershipFactory;
use App\Repository\MembershipRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly AssociationFactory $associationFactory,
        private readonly MembershipFactory $membershipFactory,
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
            'associations' => $this->isGranted('ROLE_ADMIN') ? array_merge($this->entityManager->getRepository(Association::class)->findAllWithMemberships(), $this->entityManager->getRepository(Association::class)->findAllWithoutMemberships()) : $user->getChairedAssociations(),
        ]);
    }

    #[Route('/{association}/owner', name: 'chairman_set_owner')]
    #[IsGranted('manage', 'association')]
    public function setOwner(
        #[MapEntity(mapping: ['association' => 'slug'])]
        Association $association,
        Request $request,
    ): Response {
        $userId = $request->request->get('user');
        if (!$userId) {
            $this->addFlash('warning', sprintf('Impossible de mettre à jour la présidence de %s, l\'utilisateur "%s" n\'existe pas.', $association->getName(), $userId));

            return $this->redirectToRoute('chairman_index');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $this->associationFactory->setOwner($association, $user);

        $this->addFlash('success', sprintf('La présidence de "%s" a été mise à jour.', $association->getName()));

        return $this->redirectToRoute('chairman_index');
    }

    #[Route('/{association}/anonymous_edition', name: 'chairman_anonymous_edition')]
    #[IsGranted('manage', 'association')]
    public function setAnonymousEdition(
        #[MapEntity(mapping: ['association' => 'slug'])]
        Association $association,
        Request $request,
    ): Response {
        $this->associationFactory->setAnonymousEdition($association, $request->get('anonymousPageEdition'), $request->get('anonymousEventsEdition'));

        $this->addFlash('success', sprintf('Les droits de modification de "%s" ont été mis à jour.', $association->getName()));

        return $this->redirectToRoute('chairman_index');
    }

    #[Route('/_/users/list', name: 'chairman_list_users')]
    public function listUsers(Request $request, UserRepository $userRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        if ('' === $query) {
            return new JsonResponse([]);
        }

        $data = array_map(static function (User $user) {
            return [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ];
        }, $userRepository->textualSearch($query));

        return new JsonResponse($data);
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

        $this->membershipFactory->accept($membership);

        $this->addFlash('success', $this->translator->trans('membership.accept.success', [
            'association' => $association->getName(),
            'user' => $user->getUsername(),
        ]));

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

        $this->membershipFactory->refuse($membership);

        $this->addFlash('success', $this->translator->trans('membership.refuse.success', [
            'association' => $association->getName(),
            'user' => $user->getUsername(),
        ]));

        return $this->redirectToRoute('chairman_index');
    }
}
