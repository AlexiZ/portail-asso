<?php

namespace App\Security;

use App\Entity\Association;
use App\Entity\Membership;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    public const EDIT = 'edit';
    public const MANAGE = 'manage';
    public const DELETE = 'delete';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::MANAGE, self::DELETE]) && $subject instanceof Association;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            $vote?->addReason('The user is not logged in.');

            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MODERATOR', $user->getRoles())) {
            return true;
        }

        /** @var Association $association */
        $association = $subject;
        return match ($attribute) {
            self::EDIT => $this->canEdit($association, $user, $vote),
            self::MANAGE => $this->canManage($association, $user, $vote),
            self::DELETE => $this->canDelete($association, $user, $vote),
            default => throw new \LogicException('This code should not be reached !'),
        };
    }

    private function canEdit(Association $association, User $user, ?Vote $vote): bool
    {
        if ($this->canManage($association, $user, $vote)) {
            return true;
        }

        $users = [];
        foreach ($association->getMemberships() as $member) {
            if (Membership::STATUS_ACCEPTED === $member->getStatus()) {
                $users[] = $member->getUser();
            }
        }
        if (in_array($user, $users)) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to edit this association (id : %d).',
            $user->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canManage(Association $association, User $user, ?Vote $vote): bool
    {
        if ($user === $association->getOwner()) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to manage this association (id : %d).',
            $user->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canDelete(Association $association, User $user, ?Vote $vote): bool
    {
        if ($this->canManage($association, $user, $vote)) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to delete this association (id : %d).',
            $user->getUsername(), $association->getId()
        ));

        return false;
    }
}
