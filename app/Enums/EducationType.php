<?php

namespace App\Enums;

enum EducationType: string
{
    use BaseEnum;

    case FORMAL = 'formal';
    case INFORMAL = 'informal';
}
