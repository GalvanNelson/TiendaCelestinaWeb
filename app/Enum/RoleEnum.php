<?php

namespace App\Enum;

enum RoleEnum: string
{
    case PROPIETARIO = 'propietario';
    case VENDEDOR = 'vendedor';
    case CLIENTE = 'cliente';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
