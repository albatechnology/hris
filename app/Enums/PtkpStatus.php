<?php

namespace App\Enums;

enum PtkpStatus: string
{
    use BaseEnum;

    case TK_0 = 'TK/0';
    case TK_1 = 'TK/1';
    case TK_2 = 'TK/2';
    case TK_3 = 'TK/3';
    case K_0 = 'K/0';
    case K_1 = 'K/1';
    case K_2 = 'K/2';
    case K_3 = 'K/3';
    case K_I_0 = 'K/I/0';
    case K_I_1 = 'K/I/1';
    case K_I_2 = 'K/I/2';
    case K_I_3 = 'K/I/3';
}
