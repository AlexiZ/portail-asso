<?php

namespace App\Factory;

use App\Entity\Association;
use App\Entity\Membership;
use App\Entity\User;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;

readonly class MembershipFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private Mailer $mailer,
    ) {
    }

    public function create(User $user, Association $association): void
    {
        $membership = new Membership();
        $membership->setUser($user);
        $membership->setAssociation($association);

        $this->em->persist($membership);
        $this->em->flush();

        $parameters = [
            'to' => [],
            'username' => '',
            'association' => $association->getName(),
        ];
        $chairman = $association->getOwner();
        if ($chairman instanceof User) {
            $parameters['to'][] = $chairman->getEmail();
            $parameters['username'] = $chairman->getUsername();
        } else {
            // Send the request to admins instead
            $admins = $this->em->getRepository(User::class)->findAdmins();
            foreach ($admins as $admin) {
                $parameters['to'][] = $admin->getEmail();
                $parameters['username'] = 'Admin';
            }
        }

        $this->mailer->requestMembership($parameters);
    }

    public function accept(Membership $membership): void
    {
        $membership->setStatus(Membership::STATUS_ACCEPTED);

        $this->em->persist($membership);
        $this->em->flush();

        $this->mailer->acceptMembership($membership->getUser(), $membership->getAssociation());
    }

    public function refuse(Membership $membership): void
    {
        $membership->setStatus(Membership::STATUS_REFUSED);
        $association = $membership->getAssociation();

        if ($membership->getUser() === $association->getOwner()) {
            $association->setOwner(null);
            $this->em->persist($association);
        }

        $this->em->persist($membership);
        $this->em->flush();
    }
}
