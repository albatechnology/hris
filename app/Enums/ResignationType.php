<?php

namespace App\Enums;

enum ResignationType: string
{
    use BaseEnum;

    case RESIGN = 'resign';
    case REHIRE = 'rehire';
}
