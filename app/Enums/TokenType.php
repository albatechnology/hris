<?php

namespace App\Enums;

/**
 * don't forget to update enum in tokens table, because this is enum type in tokens table
 */
enum TokenType: string
{
    use BaseEnum;

    case REFRESH_TOKEN = 'refresh_token';
}
