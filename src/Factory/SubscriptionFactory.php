<?php

namespace App\Factory;

use App\Entity\Association;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

readonly class SubscriptionFactory
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function switch(Association $association, User $user): ?Subscription
    {
        if ($subscription = $user->isSubscribedTo($association)) {
            $this->remove($subscription);

            return null;
        }

        return $this->create($association, $user);
    }

    protected function create(Association $association, User $user): Subscription
    {
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setAssociation($association);

        $user->addSubscription($subscription);

        $this->em->persist($user);
        $this->em->flush();

        return $subscription;
    }

    protected function remove(Subscription $subscription): void
    {
        $subscription->getUser()->removeSubscription($subscription);

        $this->em->remove($subscription);
        $this->em->flush();
    }
}
