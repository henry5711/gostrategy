<?php

namespace App\Enums;

/**
 * Estados de region/commune/customer. Es un enum de Laravel a propósito: la prueba
 * este trait lo hice para no usar enum en slq ya que siento que eso condenan mucho las bdd y las limita a parte de ser tedioso luego añadir valores nuevos
 */
enum Status: string
{
    case Active = 'A';
    case Inactive = 'I';
    case Trash = 'trash';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Desactivo',
            self::Trash => 'Eliminado',
        };
    }
}
