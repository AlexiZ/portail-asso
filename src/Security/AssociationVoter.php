<?php

namespace App\Security;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    public const EDIT = 'ASSOCIATION_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof Association;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // Admin ou modérateur
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_MODERATOR', $user->getRoles())) {
            return true;
        }

        // Propriétaire de l'association
        return $subject->getOwner()?->getId() === $user->getId();
    }
}
