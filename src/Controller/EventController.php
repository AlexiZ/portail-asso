<?php

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
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

    #[Route('/{id}', name: 'event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/association/{slug}/new', name: 'event_new')]
    public function new(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Association $association,
    ): Response {
        if (!$this->authChecker->isGranted('ASSOCIATION_EDIT', $association)) {
            throw $this->createAccessDeniedException();
        }

        $event = new Event();
        $event->setAssociation($association);
        $event->setCreatedBy($this->getUser());

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    public function edit(
        Request $request,
        Event $event,
    ): Response {
        if (!$this->authChecker->isGranted('ASSOCIATION_EDIT', $event->getAssociation())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('event.edit.form.confirm'));

            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'event_delete')]
    public function delete(
        Event $event,
    ): Response {
        if (!$this->authChecker->isGranted('ASSOCIATION_DELETE', $event->getAssociation())) {
            throw $this->createAccessDeniedException();
        }

        $association = clone $event->getAssociation();

        $this->em->remove($event);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('event.delete.confirm'));

        return $this->redirectToRoute('association_show', ['slug' => $association->getSlug()]);
    }
}
