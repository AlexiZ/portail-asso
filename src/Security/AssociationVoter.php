<?php

namespace App\Security;

use App\Entity\Association;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AssociationVoter extends Voter
{
    public const EDIT = 'ASSOCIATION_EDIT';
    public const DELETE = 'ASSOCIATION_DELETE';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::DELETE]) && $subject instanceof Association;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (self::DELETE === $attribute && !in_array('ROLE_ADMIN', $user->getRoles())) {
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
