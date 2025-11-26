<?php

namespace App\Security;

use App\Entity\Association;
use App\Entity\Event;
use App\Entity\Membership;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    public const NEW = 'new';
    public const NEW_EVENT = 'new_event';
    public const EDIT = 'edit';
    public const EDIT_EVENT = 'edit_event';
    public const MANAGE = 'manage';
    public const DELETE = 'delete';
    public const DELETE_EVENT = 'delete_event';

    protected function supports(string $attribute, $subject): bool
    {
        return (in_array($attribute, [self::NEW, self::NEW_EVENT, self::EDIT, self::MANAGE, self::DELETE]) && $subject instanceof Association)
            || (in_array($attribute, [self::EDIT_EVENT, self::DELETE_EVENT]) && $subject instanceof Event);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if ($user instanceof User && (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MODERATOR', $user->getRoles()))) {
            return true;
        }

        return match ($attribute) {
            self::NEW => $this->canNew($subject, $user, $vote),
            self::NEW_EVENT => $this->canNewEvent($subject, $user, $vote),
            self::EDIT => $this->canEdit($subject, $user, $vote),
            self::EDIT_EVENT => $this->canEditEvent($subject, null, $user, $vote),
            self::MANAGE => $this->canManage($subject, $user, $vote),
            self::DELETE => $this->canDelete($subject, $user, $vote),
            self::DELETE_EVENT => $this->canDeleteEvent($subject, $user, $vote),
            default => throw new \LogicException('This code should not be reached !'),
        };
    }

    private function canNew(Association $association, ?User $user, ?Vote $vote): bool
    {
        if (!$association->isEditablePageAnonymously() && !$this->canEdit($association, $user, $vote)) {
            return false;
        }

        return false;
    }

    private function canNewEvent(Association $association, ?User $user, ?Vote $vote): bool
    {
        if (!$association->isEditableEventsAnonymously() && !$this->canEditEvent(null, $association, $user, $vote)) {
            return false;
        }

        return true;
    }

    private function canEdit(Association $association, ?User $user, ?Vote $vote): bool
    {
        if ($association->isEditablePageAnonymously()) {
            return true;
        }

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
            $user?->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canEditEvent(?Event $event, ?Association $association, ?User $user, ?Vote $vote): bool
    {
        if ($event instanceof Event) {
            $association = $event->getAssociation();
        }

        if ($association->isEditableEventsAnonymously()) {
            return true;
        }

        if (!$user instanceof User) {
            $vote?->addReason('The user is not logged in.');

            return false;
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
            'The logged in user (username : %s) is not allowed to edit this association\'s events (id : %d).',
            $user->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canManage(Association $association, ?User $user, ?Vote $vote): bool
    {
        if (!$user instanceof User) {
            $vote?->addReason('The user is not logged in.');

            return false;
        }

        if ($user === $association->getOwner()) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to manage this association (id : %d).',
            $user->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canDelete(Association $association, ?User $user, ?Vote $vote): bool
    {
        if ($this->canManage($association, $user, $vote)) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to delete this association (id : %d).',
            $user?->getUsername(), $association->getId()
        ));

        return false;
    }

    private function canDeleteEvent(Event $event, ?User $user, ?Vote $vote): bool
    {
        if ($this->canEditEvent($event, null, $user, $vote)) {
            return true;
        }

        $vote?->addReason(sprintf(
            'The logged in user (username : %s) is not allowed to delete this association\'s events (id : %d).',
            $user?->getUsername(), $event->getAssociation()->getId()
        ));

        return false;
    }
}
