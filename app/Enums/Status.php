<?php

namespace App\Enums;

enum Status: string
{
    case Active = 'A';
    case Inactive = 'I';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Desactivo',
        };
    }
}
