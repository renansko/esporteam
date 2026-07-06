<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Helper = 'helper';
    case Member = 'member';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function assignableValues(): array
    {
        return [
            self::Admin->value,
            self::Teacher->value,
            self::Helper->value,
            self::Member->value,
        ];
    }

    public static function assignableValidationRule(): string
    {
        return 'in:' . implode(',', self::assignableValues());
    }

    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::values());
    }
}
