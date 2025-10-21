<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AdminUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'admin_index')]
    public function index(UserRepository $userRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepo->findAll();

        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/{id}/toggle-role', name: 'admin_toggle_role')]
    public function toggleRole(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $roles = $user->getRoles();
        if (in_array('ROLE_MODERATOR', $roles)) {
            $roles = array_diff($roles, ['ROLE_MODERATOR']);
        } else {
            $roles[] = 'ROLE_MODERATOR';
        }
        $user->setRoles($roles);

        $this->em->persist($user);
        $this->em->flush();

        $this->addFlash('success', 'Rôle mis à jour avec succès.');

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/user/new', name: 'admin_user_new')]
    public function new(Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashed = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);

            $user->setRoles([$form->get('roles')->getData()]);

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/user_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['id' => 'id'])]
        User $user,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(AdminUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashed = $hasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashed);
            }

            if (!empty($form->get('roles')->getData())) {
                $user->setRoles([$form->get('roles')->getData()]);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
