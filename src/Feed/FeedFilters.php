<?php

namespace App\Feed;

use App\Enum\AssociationCategory;

/**
 * Parses the RSS feeds query string. Invalid parameters are ignored (default applied)
 * and reported in $errors, displayed with ?debug=true.
 */
final class FeedFilters
{
    public const DEFAULT_LIMIT = 30;
    public const EVENT_SORT_FIELDS = ['startAt', 'createdAt', 'title'];
    public const ASSOCIATION_SORT_FIELDS = ['updatedAt', 'createdAt', 'name'];

    /**
     * @param string[] $categories
     * @param string[] $associationSlugs
     * @param string[] $errors
     */
    private function __construct(
        public readonly int $limit,
        public readonly bool $private,
        public readonly array $categories,
        public readonly array $associationSlugs,
        public readonly bool $past,
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly string $sortField,
        public readonly string $sortDirection,
        public array $errors,
    ) {
    }

    public static function forEvents(array $query, \DateTimeImmutable $now): self
    {
        $errors = [];
        $limit = self::parseLimit($query, $errors);
        $private = self::parseBool($query, 'private', $errors);
        $past = self::parseBool($query, 'past', $errors);
        $categories = self::parseCategories($query, $errors);
        $slugs = self::parseList(self::parseString($query, 'association', $errors));
        $from = self::parseDate($query, 'from', $errors);
        $to = self::parseDate($query, 'to', $errors)?->setTime(23, 59, 59);
        [$sortField, $sortDirection] = self::parseSort($query, self::EVENT_SORT_FIELDS, ['startAt', $past ? 'DESC' : 'ASC'], $errors);

        // Filters intersect: without "past", an earlier "from" can't bring past events back.
        $start = $past ? ($from ?? $now->modify('-1 year')) : max($from ?? $now, $now);
        $end = $to ?? max($start, $now)->modify('+1 year');

        return new self($limit, $private, $categories, $slugs, $past, $start, $end, $sortField, $sortDirection, $errors);
    }

    public function applied(): array
    {
        return [
            'limit' => -1 === $this->limit ? 'sans limite' : $this->limit,
            'private' => $this->private,
            'categories' => $this->categories,
            'association' => $this->associationSlugs,
            'past' => $this->past,
            'from' => $this->from->format('Y-m-d H:i:s'),
            'to' => $this->to->format('Y-m-d H:i:s'),
            'sort' => $this->sortField.','.$this->sortDirection,
        ];
    }

    public static function isDebug(array $query): bool
    {
        $errors = [];

        return self::parseBool($query, 'debug', $errors);
    }

    /**
     * "tags" and "categories" are aliases, both are merged.
     *
     * @return string[]
     */
    public static function parseCategories(array $query, array &$errors): array
    {
        $categories = [];

        foreach (['tags', 'categories'] as $key) {
            foreach (self::parseList(self::parseString($query, $key, $errors)) as $value) {
                if (null === AssociationCategory::tryFrom($value)) {
                    $errors[] = sprintf('%s : catégorie inconnue « %s »', $key, $value);
                    continue;
                }
                $categories[] = $value;
            }
        }

        return array_values(array_unique($categories));
    }

    /**
     * @param string[]              $allowedFields
     * @param array{string, string} $default
     *
     * @return array{string, string} field, ASC|DESC
     */
    public static function parseSort(array $query, array $allowedFields, array $default, array &$errors): array
    {
        $value = self::parseString($query, 'sort', $errors);
        if (null === $value) {
            return $default;
        }

        $parts = array_map('trim', explode(',', $value));
        $field = $parts[0];
        $direction = strtoupper($parts[1] ?? 'ASC');

        if (count($parts) > 2 || !in_array($field, $allowedFields, true) || !in_array($direction, ['ASC', 'DESC'], true)) {
            $errors[] = sprintf('sort : « %s » invalide (champs : %s ; sens : ASC ou DESC)', $value, implode(', ', $allowedFields));

            return $default;
        }

        return [$field, $direction];
    }

    /**
     * @param string[] $errors
     */
    public static function describe(array $applied, array $errors): string
    {
        $lines = ['Paramètres ignorés :'];
        foreach ($errors ?: ['(aucun)'] as $error) {
            $lines[] = '- '.$error;
        }

        $lines[] = '';
        $lines[] = 'Filtres appliqués :';
        foreach ($applied as $key => $value) {
            $value = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_array($value) => $value ? implode(', ', $value) : '(aucun)',
                default => (string) $value,
            };
            $lines[] = sprintf('- %s : %s', $key, $value);
        }

        return implode("\n", $lines)."\n";
    }

    private static function parseString(array $query, string $key, array &$errors): ?string
    {
        $value = $query[$key] ?? null;
        if (null !== $value && !is_string($value)) {
            $errors[] = sprintf('%s : valeur non textuelle', $key);

            return null;
        }

        return '' === $value ? null : $value;
    }

    /**
     * @return string[]
     */
    private static function parseList(?string $value): array
    {
        if (null === $value) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), fn (string $item) => '' !== $item)));
    }

    private static function parseBool(array $query, string $key, array &$errors): bool
    {
        $value = self::parseString($query, $key, $errors);

        if (in_array($value, ['true', '1'], true)) {
            return true;
        }
        if (null !== $value && !in_array($value, ['false', '0'], true)) {
            $errors[] = sprintf('%s : « %s » n\'est pas un booléen (true ou false)', $key, $value);
        }

        return false;
    }

    private static function parseLimit(array $query, array &$errors): int
    {
        $value = self::parseString($query, 'limit', $errors);

        if (null === $value) {
            return self::DEFAULT_LIMIT;
        }
        if ('-1' === $value || (ctype_digit($value) && (int) $value >= 1)) {
            return (int) $value;
        }

        $errors[] = sprintf('limit : « %s » n\'est ni un entier ≥ 1 ni -1', $value);

        return self::DEFAULT_LIMIT;
    }

    private static function parseDate(array $query, string $key, array &$errors): ?\DateTimeImmutable
    {
        $value = self::parseString($query, $key, $errors);
        if (null === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date || $date->format('Y-m-d') !== $value) {
            $errors[] = sprintf('%s : « %s » n\'est pas une date au format YYYY-MM-DD', $key, $value);

            return null;
        }

        return $date;
    }
}
