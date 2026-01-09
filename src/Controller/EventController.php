<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Event;
use App\Factory\EventFactory;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/event')]
class EventController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $em,
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
    }

    #[Route('/', name: 'event_list')]
    public function list(): Response
    {
        $events = $this->em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.startAt >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/{slug}', name: 'event_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Event $event,
    ): Response {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/association/{slug}/new', name: 'event_new')]
    #[IsGranted('new_event', 'association')]
    public function new(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
        EventFactory $eventFactory,
    ): Response {
        if (!$this->authChecker->isGranted('edit', $association)) {
            throw $this->createAccessDeniedException();
        }

        $event = new Event();
        $event->setAssociation($association);
        $event->setCreatedBy($this->getUser());

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventFactory->processPoster($event, $form);

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('event.add.form.confirm'));

            return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
        }

        return $this->render('event/new.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit')]
    #[IsGranted('edit_event', 'event')]
    public function edit(
        Request $request,
        Event $event,
        EventFactory $eventFactory,
    ): Response {
        if (!$this->authChecker->isGranted('edit', $event->getAssociation())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventFactory->processPoster($event, $form);

            $this->em->persist($event);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('event.edit.form.confirm'));

            return $this->redirectToRoute('event_show', ['slug' => $event->getSlug()]);
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'event_duplicate')]
    #[IsGranted('edit_event', 'event')]
    public function duplicate(
        Request $request,
        Event $event,
    ): Response {
        if (!$this->authChecker->isGranted('edit', $event->getAssociation())) {
            throw $this->createAccessDeniedException();
        }

        $duped = new Event();
        $duped->setAssociation($event->getAssociation());
        $duped->setCreatedBy($this->getUser());
        $duped->setTitle($event->getTitle());
        $duped->setSlug($event->getSlug());
        $duped->setShortDescription($event->getShortDescription());
        $duped->setLogoFilename($event->getLogoFilename());
        $duped->setLongDescription($event->getLongDescription());
        $duped->setStartAt($event->getStartAt());
        $duped->setEndAt($event->getEndAt());

        $form = $this->createForm(EventType::class, $duped);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($duped);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('event.add.form.confirm'));

            return $this->redirectToRoute('event_show', ['slug' => $duped->getSlug()]);
        }

        return $this->render('event/new.html.twig', [
            'form' => $form,
            'event' => $duped,
        ]);
    }

    #[Route('/{id}/delete', name: 'event_delete')]
    #[IsGranted('delete_event', 'event')]
    public function delete(
        Event $event,
    ): Response {
        if (!$this->authChecker->isGranted('delete', $event->getAssociation())) {
            throw $this->createAccessDeniedException();
        }

        $association = clone $event->getAssociation();

        $this->em->remove($event);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('event.delete.confirm'));

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }
}
