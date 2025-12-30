<?php

namespace App\Security;

/**
 * Central definition of application roles.
 *
 * Symfony ultimately works with role strings ("ROLE_…"), so we keep the enum backed by string.
 */
enum Role: string
{
    case USER = 'ROLE_USER';
    case ROOT = 'ROLE_ROOT';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }

    public static function fromStringOrNull(string $role): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $role) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Normalizes a list of role strings.
     * - drops unknown roles
     * - de-dupes
     * - guarantees ROLE_USER
     *
     * @param list<string> $roles
     * @return list<string>
     */
    public static function normalizeStrings(array $roles): array
    {
        $normalized = [];

        foreach ($roles as $role) {
            $role = trim((string) $role);
            if ($role === '') {
                continue;
            }
            if (self::fromStringOrNull($role) === null) {
                continue;
            }
            $normalized[] = $role;
        }

        $normalized[] = self::USER->value;

        return array_values(array_unique($normalized));
    }
}

