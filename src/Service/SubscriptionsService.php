<?php

namespace App\Service;

use App\Entity\AssociationRevision;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubscriptionsService
{
    final public const REPORT_DELAY = '1 week ago';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Mailer $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function sendWeeklyReports(?string $from = null): int
    {
        $usersWithSubscription = $this->em->getRepository(User::class)->withSubscriptions();

        /** @var User $user */
        foreach ($usersWithSubscription as $user) {
            $data = [];

            foreach ($user->getSubscriptions() as $subscription) {
                $association = $subscription->getAssociation();
                $changes = [];

                // La page asso a été modifiée depuis le dernier rapport
                if ($association->getRevisionsSince(new \DateTimeImmutable($from ?: self::REPORT_DELAY))->count() > 0) {
                    /** @var AssociationRevision $lastRevision */
                    $lastRevision = $association->getRevisions()->last();
                    $changes[] = [
                        'date' => $lastRevision->getCreatedAt(),
                        'link' => $this->urlGenerator->generate('association_show', ['slug' => $association->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'title' => $this->translator->trans('email.subscriptions_report.changes.association', ['%association%' => $association->getName()]),
                    ];
                }

                // Des événements ont été créés cette semaine par cette association
                /** @var Event $event */
                foreach ($association->getEventsSince(new \DateTimeImmutable(self::REPORT_DELAY)) as $event) {
                    $changes[] = [
                        'date' => $event->getStartAt(),
                        'link' => $this->urlGenerator->generate('event_show', ['slug' => $event->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'title' => $event->getTitle(),
                    ];
                }

                if (!empty($changes)) {
                    $data[] = [
                        'title' => $association->getName(),
                        'changes' => $changes,
                    ];
                }
            }

            $this->mailer->subscriptionReport($user, $data);
        }

        return count($usersWithSubscription);
    }
}
