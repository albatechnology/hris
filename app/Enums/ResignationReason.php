<?php

namespace App\Enums;

enum ResignationReason: string
{
    use BaseEnum;

    case RESIGNED_VOLUNTARILY_WITHOUT_PRESSURE_IN_COMPLIANCE = 'Resigned voluntarily without pressure, in compliance with company regulations';
    case RESIGNED_VOLUNTARILY_WITHOUT_PRESSURE_NOT_IN_COMPLIANCE = 'Resigned voluntarily without pressure, not in compliance with company regulations';
    case DID_NOT_PASS_PROBATION_PERIOD = 'Did not pass the probation period';
    case COMPLETION_OF_FIXED_TERM_AGREEMENT = 'Completion of a fixed-term employment agreement (PKWT)';
    case PASSED_AWAY = 'Employee passed away';
    case REACHES_RETIREMENT_AGE = 'Employee reaches retirement age';
}
