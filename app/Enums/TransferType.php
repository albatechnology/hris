<?php

namespace App\Enums;

enum TransferType: string
{
    use BaseEnum;

    case PROMOTION = 'promotion';
    case MUTATION = 'mutation';
    case DEMOTION = 'demotion';
    case EXTEND_CONTRACT = 'extend_contract';
    case ROTATION = 'rotation';
    case OTHER = 'other';
}
