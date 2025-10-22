<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Membership;
use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/mon-compte')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/', name: 'user_account')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw $this->createAccessDeniedException();
        }

        // Formulaire changement de mot de passe et prénom/nom
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );
            }
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('user_account.edit.form.success'));

            return $this->redirectToRoute('user_account');
        }

        // Suppression du compte
        if ($request->isMethod('POST') && $request->request->get('delete_account')) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->container->get('security.token_storage')->setToken();
            $request->getSession()->invalidate();

            $this->addFlash('info', $this->translator->trans('user_account.delete_account.success'));

            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-abonnements', name: 'user_subscriptions')]
    public function subscriptions(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw $this->createAccessDeniedException();
        }

        $associations = $this->entityManager->getRepository(Association::class)->getUserSubs($user);

        return $this->render('user/subscriptions.html.twig', [
            'associations' => $associations,
        ]);
    }

    #[Route('/demande-adhesion/{association}', name: 'user_ask_membership')]
    public function askMembership(
        #[MapEntity(mapping: ['association' => 'slug'])]
        Association $association,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw $this->createAccessDeniedException();
        }

        $membership = $this->entityManager->getRepository(Membership::class)->findOneBy([
            'user' => $user,
            'association' => $association,
        ]);
        if (!$membership instanceof Membership) {
            $membership = new Membership();
            $membership->setUser($user);
            $membership->setAssociation($association);

            $this->entityManager->persist($membership);
            $this->entityManager->flush();
        }

        $this->addFlash('success', $this->translator->trans('user_account.ask_membership.success'));

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }
}
