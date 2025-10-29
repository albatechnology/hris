<?php

namespace App\Enums;

enum ReprimandType: string
{
    use BaseEnum;

    case SP_1 = 'SP 1';
    case SP_2 = 'SP 2';
    case SP_3 = 'SP 3';
    case ST = 'Surat Teguran';
    case TOLERANCE = 'tolerance';
    case NO_EXTRA_OFF = 'no_extra_off';
    case LATE_WARNING_LETTER = 'late_warning_letter';
    case LATE_WARNING_LETTER_AND_CALL_TO_HR = 'late_warning_letter_and_call_to_HR';
    case CUT_LEAVE_AND_WARNING_LETTER = 'cut_leave_and_warning_letter';
    case CUT_LEAVE_AND_SP_1 = 'cut_leave_and_SP_1';
    case CUT_LEAVE_AND_SP_2 = 'cut_leave_and_SP_2';
    case CUT_LEAVE_AND_SP_3 = 'cut_leave_and_SP_3';
}
