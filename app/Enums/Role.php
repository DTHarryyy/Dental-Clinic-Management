<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Dentist = 'dentist';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Dentist => 'Dentist',
            self::Receptionist => 'Receptionist',
        };
    }
}
