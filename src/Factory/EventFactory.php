<?php

namespace App\Factory;

use App\Entity\Event;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class EventFactory
{
    public function __construct(
        private SluggerInterface $slugger,
        private TranslatorInterface $translator,
        #[Autowire('%kernel.project_dir%/public/uploads')]
        private string $uploadsDirectory,
    ) {
    }

    public function processPoster(Event $event, FormInterface $form): void
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
}
