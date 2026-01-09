<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\User;
use App\Factory\AssociationFactory;
use App\Form\AssociationType;
use App\Repository\AssociationRevisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

#[Route('/association')]
class AssociationController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $em,
        private readonly AssociationFactory $associationFactory,
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
    ): Response {
        $association = new Association();
        if ($name = $request->query->get('name')) {
            $association->setName($name);
        }
        $form = $this->createForm(AssociationType::class, $association);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->associationFactory->populateAssociation($association, $request, $form);

            $this->addFlash('success', $this->translator->trans('association.add.confirm'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        return $this->render('association/new.html.twig', [
            'form' => $form->createView(),
            'association' => $association,
        ]);
    }

    #[Route('/filtrer', name: 'association_filter', options: ['expose' => true])]
    public function filter(Request $request): Response
    {
        $queriedCategory = $request->query->get('q');
        $associations = $this->em->getRepository(Association::class)->findAll();
        if ('all' !== $queriedCategory) {
            $associations = $this->em->getRepository(Association::class)->findAllWithCategory($queriedCategory);
        }

        return $this->render('association/_list.html.twig', ['associations' => $associations]);
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
    ): Response {
        $beforeAssociation = clone $association;
        $formAssociation = $this->createForm(AssociationType::class, $association);
        $formAssociation->handleRequest($request);

        if ($formAssociation->isSubmitted() && $formAssociation->isValid()) {
            $this->associationFactory->createRevision($beforeAssociation, $association, $request, $formAssociation);

            $this->addFlash('success', $this->translator->trans('association.edit.confirm'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        return $this->render('association/edit.html.twig', [
            'association' => $association,
            'formAssociation' => $formAssociation->createView(),
        ]);
    }

    #[Route('/{slug}/delete', name: 'association_delete', requirements: ['id' => '\d+'])]
    #[IsGranted('delete', 'association')]
    public function delete(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
    ): Response {
        $this->em->remove($association);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('association.delete.confirm'));

        return $this->redirectToRoute('homepage');
    }

    #[Route('/{slug}/revision/{revisionId}/apercu', name: 'association_rollback_preview')]
    public function rollbackPreview(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        int $revisionId,
        AssociationRevisionRepository $revRepo,
        Environment $twig,
    ): Response {
        $revision = $revRepo->find($revisionId);

        if (!$revision || $revision->getAssociation()->getId() !== $association->getId()) {
            return new JsonResponse('Révision invalide.');
        }

        $before = json_decode($revision->getContentBefore(), true);
        $after = json_decode($revision->getContentAfter(), true);

        return new JsonResponse($twig->render('revision/diff.html.twig', [
            'revision' => $revision,
            'before' => $before,
            'after' => $after,
            'association' => $association,
        ]));
    }

    #[Route('/{slug}/revision/{revisionId}/confirmer', name: 'association_rollback')]
    #[IsGranted('edit', 'association')]
    public function rollbackConfirm(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        int $revisionId,
        AssociationRevisionRepository $revRepo,
    ): Response {
        $revision = $revRepo->find($revisionId);
        if (!$revision || $revision->getAssociation()->getId() !== $association->getId()) {
            $this->addFlash('danger', 'Révision invalide.');

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        $beforeAssociation = clone $association;
        $this->associationFactory->applyRevision($revision, $association);

        // Créer une nouvelle révision pour le rollback
        $this->associationFactory->createRevision($beforeAssociation, $association, $request);

        $this->addFlash('success', 'Rollback effectué avec succès.');

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }

    #[Route('/{slug}/suivre', name: 'association_subscribe')]
    public function subscribe(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            $this->addFlash('danger', $this->translator->trans('association.subscription.error.no_user'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        if ($user->isSubscribedTo($association)) {
            $user->removeSubscription($association);
            $this->addFlash('success', $this->translator->trans('association.unsubscription.success'));
        } else {
            $user->addSubscription($association);
            $this->addFlash('success', $this->translator->trans('association.subscription.success'));
        }
        $this->em->persist($user);
        $this->em->flush();

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }
}
