<?php

namespace App\Factory;

use App\Entity\Event;
use RRule\RRule;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class EventFactory
{
    public function __construct(
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        private TokenStorageInterface $tokenStorage,
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadsDirectory,
    ) {
    }

    public function processForm(Event $event, FormInterface $form): void
    {
        $this->uploadPoster($event, $form);
    }

    protected function uploadPoster(Event $event, FormInterface $form): void
    {
        /** @var UploadedFile $posterFile */
        $posterFile = $form->get('poster')->getData();
        if ($posterFile) {
            $originalFilename = pathinfo($posterFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$posterFile->guessExtension();

            try {
                $posterFile->move($this->uploadsDirectory, $newFilename);
                $event->setPosterFilename($newFilename);
            } catch (FileException) {
                $form->get('logo')->addError(new FormError($this->translator->trans('event.form.error.poster')));
            }
        }
    }

    protected function handleRRule(Event $event, FormInterface $form): void
    {
        dump($form->get('recurrenceRule')->getData());
        $rruleData = $form->get('recurrenceRule')->getData();
        $rrule = new RRule($rruleData);
        dump($rrule);
        $occurrences = $rrule->getOccurrencesAfter($event->getStartAt());
        dump($occurrences);
    }

    public function duplicate(Event $event): Event
    {

        $duped = new Event();
        $duped->setAssociation($event->getAssociation());
        $duped->setCreatedBy($this->tokenStorage->getToken()->getUser());
        $duped->setTitle($event->getTitle());
        $duped->setSlug($event->getSlug());
        $duped->setShortDescription($event->getShortDescription());
        $duped->setPosterFilename($event->getPosterFilename());
        $duped->setLongDescription($event->getLongDescription());
        $duped->setStartAt($event->getStartAt());
        $duped->setEndAt($event->getEndAt());
        $duped->setRecurrenceRule($event->getRecurrenceRule());

        return $duped;
    }
}
