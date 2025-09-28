<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\AssociationRevision;
use App\Entity\User;
use App\Form\AssociationType;
use App\Repository\AssociationRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/association')]
class AssociationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $em,
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
    }

    #[Route('/pre-nouvelle', name: 'association_pre_new', options: ['expose' => true])]
    public function preNew(
        Request $request,
        SluggerInterface $slugger,
        SerializerInterface $serializer,
    ): Response {
        $association = new Association();
        $form = $this->createForm(AssociationType::class, $association, ['pre_new' => true]);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            $results = $this->em->getRepository(Association::class)->searchByName($request->get('q'));

            return new JsonResponse($serializer->serialize($results, 'json', ['groups' => ['autocomplete']]), 200, [], true);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = strtolower($slugger->slug($association->getName()));
            $association->setSlug($slug);
            $existingAsso = $this->em->getRepository(Association::class)->findOneBy(['slug' => $slug]);
            if ($existingAsso instanceof Association) {
                $form->addError(new FormError($this->translator->trans('association.form.error.already_exists', [
                    '%assocationLink%' => $this->generateUrl('association_show', ['id' => $association->getId()]),
                    '%associationName%' => $association->getName(),
                ])));

                return $this->redirectToRoute('association_pre_new');
            }
        }

        return $this->render('association/pre-new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/nouvelle', name: 'association_new')]
    public function new(
        Request $request,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadsDirectory,
    ): Response {
        $association = new Association();
        if ($name = $request->query->get('name')) {
            $association->setName($name);
        }
        $form = $this->createForm(AssociationType::class, $association);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = strtolower($slugger->slug($association->getName()));
            $association->setSlug($slug);
            $association->setCreatedBy($request->server->get('REMOTE_ADDR'));
            $association->setUpdatedBy($request->server->get('REMOTE_ADDR'));
            if ($this->getUser() instanceof User) {
                $association->setOwner($this->getUser());
                $association->setCreatedBy($this->getUser()->getUsername());
                $association->setUpdatedBy($this->getUser()->getUsername());
            }

            $this->processAssociationLogo($association, $form, $slugger, $uploadsDirectory);

            $this->em->persist($association);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('association.add.confirm'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        return $this->render('association/new.html.twig', [
            'form' => $form->createView(),
            'association' => $association,
        ]);
    }

    #[Route('/{slug}', name: 'association_show', options: ['expose' => true])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
    ): Response {
        return $this->render('association/show.html.twig', [
            'association' => $association,
        ]);
    }

    #[Route('/{slug}/modifier', name: 'association_edit')]
    public function edit(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        Request $request,
        SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')] string $uploadsDirectory,
    ): Response {
        $formAssociation = $this->createForm(AssociationType::class, $association);
        $formAssociation->handleRequest($request);

        if ($formAssociation->isSubmitted() && $formAssociation->isValid()) {
            $revision = new AssociationRevision();
            $revision->setAssociation($association);
            $revision->setContentBefore($association->getContent());
            $revision->setContentAfter($formAssociation->get('content')->getData());
            $revision->setApproved($this->isGranted('ROLE_MODERATOR'));

            $this->processAssociationLogo($association, $formAssociation, $slugger, $uploadsDirectory);

            $association->setUpdatedAt(new \DateTimeImmutable());
            $association->setUpdatedBy($request->server->get('REMOTE_ADDR'));
            $revision->setCreatedBy($request->server->get('REMOTE_ADDR'));
            if ($this->getUser() instanceof User) {
                $association->setUpdatedBy($this->getUser()->getUsername());
                $revision->setCreatedBy($this->getUser()->getUsername());
            }

            $this->em->persist($revision);
            $this->em->persist($association);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('association.edit.confirm'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        return $this->render('association/edit.html.twig', [
            'association' => $association,
            'formAssociation' => $formAssociation->createView(),
        ]);
    }

    private function processAssociationLogo(Association $association, FormInterface $formAssociation, SluggerInterface $slugger, string $uploadsDirectory): void
    {
        /** @var UploadedFile $logoFile */
        $logoFile = $formAssociation->get('logo')->getData();
        if ($logoFile) {
            $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

            try {
                $logoFile->move($uploadsDirectory, $newFilename);
                $association->setLogoFilename($newFilename);
            } catch (FileException) {
                $formAssociation->get('logo')->addError(new FormError($this->translator->trans('association.form.error.logo')));
            }
        }
    }

    #[Route('/{slug}/revision/{revisionId}/apercu', name: 'association_rollback_preview')]
    public function rollbackPreview(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        int $revisionId,
        AssociationRevisionRepository $revRepo,
    ): Response {
        $revision = $revRepo->find($revisionId);

        if (!$revision || $revision->getAssociation()->getId() !== $association->getId()) {
            return new JsonResponse('Révision invalide.');
        }

        return new JsonResponse($revision->getContentAfter());
    }

    #[Route('/{slug}/revision/{revisionId}/confirmer', name: 'association_rollback')]
    public function rollbackConfirm(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        int $revisionId,
        AssociationRevisionRepository $revRepo,
    ): Response {
        if (!$this->authChecker->isGranted('ASSOCIATION_EDIT', $association)) {
            throw $this->createAccessDeniedException();
        }

        $revision = $revRepo->find($revisionId);

        if (!$revision || $revision->getAssociation()->getId() !== $association->getId()) {
            $this->addFlash('danger', 'Révision invalide.');

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        // Créer une nouvelle révision pour le rollback
        $rollbackRevision = new AssociationRevision();
        $rollbackRevision->setAssociation($association);
        $rollbackRevision->setContentBefore($association->getContent());
        $rollbackRevision->setContentAfter($revision->getContentAfter());
        $rollbackRevision->setApproved(true);
        $rollbackRevision->setCreatedBy($this->getUser());

        $association->setContent($revision->getContentAfter());
        $association->setUpdatedAt(new \DateTimeImmutable());

        $this->em->persist($rollbackRevision);
        $this->em->persist($association);
        $this->em->flush();

        $this->addFlash('success', 'Rollback effectué avec succès.');

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }
}
