<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Dentist = 'dentist';
    case Receptionist = 'receptionist';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Dentist => 'Dentist',
            self::Receptionist => 'Receptionist',
            self::Patient => 'Patient',
        };
    }

    public function isStaff(): bool
    {
        return in_array($this, [self::Admin, self::Dentist, self::Receptionist], true);
    }
}
