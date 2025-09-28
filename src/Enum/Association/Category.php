<?php

namespace App\Enum\Association;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Category: string implements TranslatableInterface
{
    case Animals = 'animals';
    case ArtsCulture = 'arts-culture';
    case CraftsCooking = 'crafts-cooking';
    case WellBeing = 'well-being';
    case EcologyEnvironment = 'ecology-environment';
    case Education = 'education';
    case Leisure = 'leisure';
    case Music = 'music';
    case Heritage = 'heritage';
    case ProfessionalStudentNetwork = 'professional-student-network';
    case Health = 'health';
    case ScienceTechnology = 'science-technology';
    case SolidaritySocialAction = 'solidarity-social-action';
    case Spirituality = 'spirituality';
    case Sport = 'sport';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Animals => $translator->trans('association.category.animals', locale: $locale),
            self::ArtsCulture => $translator->trans('association.category.arts-culture', locale: $locale),
            self::CraftsCooking => $translator->trans('association.category.crafts-cooking', locale: $locale),
            self::WellBeing => $translator->trans('association.category.well-being', locale: $locale),
            self::EcologyEnvironment => $translator->trans('association.category.ecology-environment', locale: $locale),
            self::Education => $translator->trans('association.category.education', locale: $locale),
            self::Leisure => $translator->trans('association.category.leisure', locale: $locale),
            self::Music => $translator->trans('association.category.music', locale: $locale),
            self::Heritage => $translator->trans('association.category.heritage', locale: $locale),
            self::ProfessionalStudentNetwork => $translator->trans('association.category.professional-student-network', locale: $locale),
            self::Health => $translator->trans('association.category.health', locale: $locale),
            self::ScienceTechnology => $translator->trans('association.category.science-technology', locale: $locale),
            self::SolidaritySocialAction => $translator->trans('association.category.solidarity-social-action', locale: $locale),
            self::Spirituality => $translator->trans('association.category.spirituality', locale: $locale),
            self::Sport => $translator->trans('association.category.sport', locale: $locale),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Animals, self::EcologyEnvironment, self::Health => 'primary',
            self::ArtsCulture, self::WellBeing, self::Heritage, self::SolidaritySocialAction => 'danger',
            self::Education, self::Spirituality, self::CraftsCooking => 'info',
            self::ScienceTechnology, self::ProfessionalStudentNetwork => 'success',
            self::Leisure, self::Music, self::Sport => 'warning',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Animals => 'fa fa-paw',
            self::ArtsCulture => 'fa fa-palette',
            self::CraftsCooking => 'fa fa-hands',
            self::WellBeing => 'fa fa-shield-heart',
            self::EcologyEnvironment => 'fa fa-seedling',
            self::Education => 'fa fa-graduation-cap',
            self::Leisure => 'fa fa-compass-drafting',
            self::Music => 'fa fa-music',
            self::Heritage => 'fa fa-chess-rook',
            self::ProfessionalStudentNetwork => 'fa fa-briefcase',
            self::Health => 'fa fa-user-nurse',
            self::ScienceTechnology => 'fa fa-gears',
            self::SolidaritySocialAction => 'fa fa-hand-holding-medical',
            self::Spirituality => 'fa fa-dove',
            self::Sport => 'fa fa-volleyball',
        };
    }
}
