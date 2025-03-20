<?php

namespace App\Enums;

enum LoanType: string
{
    use BaseEnum;

    case LOAN = 'loan';
    case INSURANCE = 'insurance';
}
