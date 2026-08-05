<?php

namespace Src\Identity\Domain\ValueObjects;

use InvalidArgumentException;

/** Allowed agriAid platform roles. */
final class PlatformRole
{
    public const FARMER = 'farmer';

    public const WAREHOUSE = 'warehouse';

    public const LENDER = 'lender';

    public const BUYER = 'buyer';

    public const GOVERNMENT = 'government';

    public const ADMIN = 'admin';

    /** @var list<string> */
    public const ALL = [
        self::FARMER,
        self::WAREHOUSE,
        self::LENDER,
        self::BUYER,
        self::GOVERNMENT,
        self::ADMIN,
    ];

    /** Roles that require an organisation access code at registration. */
    public const CODED = [
        self::LENDER,
        self::WAREHOUSE,
        self::GOVERNMENT,
    ];

    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $role): self
    {
        $role = strtolower(trim($role));
        if (! in_array($role, self::ALL, true)) {
            throw new InvalidArgumentException("Unknown platform role: {$role}");
        }

        return new self($role);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function requiresAccessCode(): bool
    {
        return in_array($this->value, self::CODED, true);
    }
}
