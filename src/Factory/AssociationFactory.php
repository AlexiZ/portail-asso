<?php

namespace App\Factory;

use App\Entity\Association;
use App\Entity\AssociationRevision;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class AssociationFactory
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        private EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadsDirectory,
    ) {
    }

    public function populateAssociation(Association $association, Request $request, FormInterface $form): void
    {
        $slug = strtolower($this->slugger->slug($association->getName()));
        $association->setSlug($slug);
        $association->setCreatedBy($request->server->get('REMOTE_ADDR'));
        $association->setUpdatedBy($request->server->get('REMOTE_ADDR'));

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if ($user instanceof User) {
            $association->setOwner($user);
            $association->setCreatedBy($user->getUsername());
            $association->setUpdatedBy($user->getUsername());
        }

        $this->processAssociationLogo($association, $form);

        $this->em->persist($association);
        $this->em->flush();
    }

    public function createRevision(Association $associationBefore, Association $associationAfter, Request $request, ?FormInterface $form = null): void
    {
        /** @var User|null $user */
        $user = $this->tokenStorage->getToken()?->getUser();
        $revision = new AssociationRevision();
        $revision->setAssociation($associationAfter);
        $revision->setContentBefore(json_encode($associationBefore->serialize()));
        $revision->setContentAfter(json_encode($associationAfter->serialize()));

        if ($form) {
            $this->processAssociationLogo($associationAfter, $form);
        }

        $associationAfter->setUpdatedAt(new \DateTimeImmutable());
        $associationAfter->setUpdatedBy($request->server->get('REMOTE_ADDR'));
        $revision->setCreatedBy($request->server->get('REMOTE_ADDR'));
        $revision->setApproved(false);
        if ($user instanceof User) {
            $associationAfter->setUpdatedBy($user->getUsername());
            $revision->setCreatedBy($user->getUsername());
            $revision->setApproved($user->hasRole('ROLE_MODERATOR'));
        }

        $this->em->persist($revision);
        $this->em->persist($associationAfter);
        $this->em->flush();
    }

    public function applyRevision(AssociationRevision $revision, Association $association): void
    {
        $association->unserialize(json_decode($revision->getContentAfter(), true));

        $this->em->persist($association);
        $this->em->flush();
    }

    private function processAssociationLogo(Association $association, FormInterface $formAssociation): void
    {
        /** @var UploadedFile $logoFile */
        $logoFile = $formAssociation->get('logo')->getData();
        if ($logoFile) {
            $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

            try {
                $logoFile->move($this->uploadsDirectory, $newFilename);
                $association->setLogoFilename($newFilename);
            } catch (FileException) {
                $formAssociation->get('logo')->addError(new FormError($this->translator->trans('association.form.error.logo')));
            }
        }
    }
}
