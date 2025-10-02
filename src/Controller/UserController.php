<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\User;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route('/', name: 'user_account')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw $this->createAccessDeniedException();
        }

        // Formulaire changement de mot de passe
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword(
                $passwordHasher->hashPassword($user, $plainPassword)
            );
            $em->flush();

            $this->addFlash('success', $translator->trans('user_account.change_password.form.success'));
            return $this->redirectToRoute('user_account');
        }

        // Suppression du compte
        if ($request->isMethod('POST') && $request->request->get('delete_account')) {
            $em->remove($user);
            $em->flush();
            $this->container->get('security.token_storage')->setToken();
            $request->getSession()->invalidate();

            $this->addFlash('info', $translator->trans('user_account.delete_account.success'));
            return $this->redirectToRoute('homepage');
        }

        return $this->render('user/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-abonnements', name: 'user_subscriptions')]
    public function charter(EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user instanceof PasswordAuthenticatedUserInterface) {
            throw $this->createAccessDeniedException();
        }

        $associations = $em->getRepository(Association::class)->getUserSubs($user);

        return $this->render('user/subscriptions.html.twig', [
            'associations' => $associations,
        ]);
    }
}
