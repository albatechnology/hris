<?php

namespace App\Enums;

enum ReprimandType: string
{
    use BaseEnum;

    case SP_1 = 'SP 1';
    case SP_2 = 'SP 2';
    case SP_3 = 'SP 3';
    case SP_4 = 'SP 4';
    case SP_5 = 'SP 5';
}
