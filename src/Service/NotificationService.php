<?php

namespace App\Service;

use App\Entity\Association;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $em;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $em)
    {
        $this->mailer = $mailer;
        $this->em = $em;
    }

    public function notifySubscribers(Association $association, string $message, string $subject = 'Modification Wiki Plab')
    {
        $subs = $this->em->getRepository(Subscription::class)
            ->findBy(['association' => $association]);

        foreach ($subs as $sub) {
            $user = $sub->getUser();
            $email = (new Email())
                ->from('noreply@wikiplab.fr')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($message);

            $this->mailer->send($email);
        }
    }
}
