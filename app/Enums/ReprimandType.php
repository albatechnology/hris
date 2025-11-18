<?php

namespace App\Enums;

enum ReprimandType: string
{
    use BaseEnum;

    case TOLERANCE = 'tolerance';

    // tipe yang reusable
    case NO_EXTRA_OFF = 'no_extra_off';
    case LATE_WARNING_LETTER = 'late_warning_letter';
    case LATE_WARNING_LETTER_AND_CALL_TO_HR = 'late_warning_letter_and_call_to_hr';

    case CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER = 'cut_leave_half_day_and_warning_letter';
    case CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER = 'cut_leave_one_day_and_warning_letter';
    case CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER = 'cut_leave_one_half_day_and_warning_letter';
    case CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER = 'cut_leave_two_day_and_warning_letter';
    case CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER = 'cut_leave_two_half_day_and_warning_letter';
    case CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER = 'cut_leave_three_day_and_warning_letter';
    case CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER = 'cut_leave_three_half_day_and_warning_letter';
    case CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER = 'cut_leave_four_day_and_warning_letter';
    case CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER = 'cut_leave_four_half_day_and_warning_letter';
    case CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER = 'cut_leave_five_day_and_warning_letter';

    // tipe selain diatas tidak bisa dibuat reusable, makanya bikin satu satu
    case SP_1 = 'sp_1';
    case SP_2 = 'sp_2';
    case SP_3 = 'sp_3';

    case CUT_LEAVE_HALF_DAY_AND_SP_1 = 'cut_leave_half_day_and_sp_1';
    case CUT_LEAVE_ONE_DAY_AND_SP_1 = 'cut_leave_one_day_and_sp_1';
    case CUT_LEAVE_ONE_HALF_DAY_AND_SP_1 = 'cut_leave_one_half_day_and_sp_1';
    case CUT_LEAVE_TWO_DAY_AND_SP_1 = 'cut_leave_two_day_and_sp_1';
    case CUT_LEAVE_TWO_HALF_DAY_AND_SP_1 = 'cut_leave_two_half_day_and_sp_1';
    case CUT_LEAVE_THREE_DAY_AND_SP_1 = 'cut_leave_three_day_and_sp_1';
    case CUT_LEAVE_THREE_HALF_DAY_AND_SP_1 = 'cut_leave_three_half_day_and_sp_1';
    case CUT_LEAVE_FOUR_DAY_AND_SP_1 = 'cut_leave_four_day_and_sp_1';
    case CUT_LEAVE_FOUR_HALF_DAY_AND_SP_1 = 'cut_leave_four_half_day_and_sp_1';
    case CUT_LEAVE_FIVE_DAY_AND_SP_1 = 'cut_leave_five_day_and_sp_1';

    case CUT_LEAVE_HALF_DAY_AND_SP_2 = 'cut_leave_half_day_and_sp_2';
    case CUT_LEAVE_ONE_DAY_AND_SP_2 = 'cut_leave_one_day_and_sp_2';
    case CUT_LEAVE_ONE_HALF_DAY_AND_SP_2 = 'cut_leave_one_half_day_and_sp_2';
    case CUT_LEAVE_TWO_DAY_AND_SP_2 = 'cut_leave_two_day_and_sp_2';
    case CUT_LEAVE_TWO_HALF_DAY_AND_SP_2 = 'cut_leave_two_half_day_and_sp_2';
    case CUT_LEAVE_THREE_DAY_AND_SP_2 = 'cut_leave_three_day_and_sp_2';
    case CUT_LEAVE_THREE_HALF_DAY_AND_SP_2 = 'cut_leave_three_half_day_and_sp_2';
    case CUT_LEAVE_FOUR_DAY_AND_SP_2 = 'cut_leave_four_day_and_sp_2';
    case CUT_LEAVE_FOUR_HALF_DAY_AND_SP_2 = 'cut_leave_four_half_day_and_sp_2';
    case CUT_LEAVE_FIVE_DAY_AND_SP_2 = 'cut_leave_five_day_and_sp_2';

    case CUT_LEAVE_HALF_DAY_AND_SP_3 = 'cut_leave_half_day_and_sp_3';
    case CUT_LEAVE_ONE_DAY_AND_SP_3 = 'cut_leave_one_day_and_sp_3';
    case CUT_LEAVE_ONE_HALF_DAY_AND_SP_3 = 'cut_leave_one_half_day_and_sp_3';
    case CUT_LEAVE_TWO_DAY_AND_SP_3 = 'cut_leave_two_day_and_sp_3';
    case CUT_LEAVE_TWO_HALF_DAY_AND_SP_3 = 'cut_leave_two_half_day_and_sp_3';
    case CUT_LEAVE_THREE_DAY_AND_SP_3 = 'cut_leave_three_day_and_sp_3';
    case CUT_LEAVE_THREE_HALF_DAY_AND_SP_3 = 'cut_leave_three_half_day_and_sp_3';
    case CUT_LEAVE_FOUR_DAY_AND_SP_3 = 'cut_leave_four_day_and_sp_3';
    case CUT_LEAVE_FOUR_HALF_DAY_AND_SP_3 = 'cut_leave_four_half_day_and_sp_3';
    case CUT_LEAVE_FIVE_DAY_AND_SP_3 = 'cut_leave_five_day_and_sp_3';

    public function getDescription(): string
    {
        return match ($this) {
            self::TOLERANCE => 'Toleransi',

            // tipe yang reusable
            self::NO_EXTRA_OFF => 'Terlambat (tidak mendapat Extra Off)',
            self::LATE_WARNING_LETTER => 'Surat Teguran Keterlambatan',
            self::LATE_WARNING_LETTER_AND_CALL_TO_HR => 'Surat Teguran Keterlambatan dan Panggilan ke HR',

            // tipe selain diatas tidak bisa dibuat reusable, makanya bikin satu satu
            self::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER => 'Potong cuti 0,5 hari + Surat Teguran',
            self::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER => 'Potong cuti 1 hari + Surat Teguran',
            self::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER => 'Potong cuti 1,5 hari + Surat Teguran',
            self::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER => 'Potong cuti 2 hari + Surat Teguran',
            self::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER => 'Potong cuti 2,5 hari + Surat Teguran',
            self::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER => 'Potong cuti 3 hari + Surat Teguran',
            self::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER => 'Potong cuti 3,5 hari + Surat Teguran',
            self::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER => 'Potong cuti 4 hari + Surat Teguran',
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER => 'Potong cuti 4,5 hari + Surat Teguran',
            self::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER => 'Potong cuti 5 hari + Surat Teguran',

            self::CUT_LEAVE_HALF_DAY_AND_SP_1 => 'Potong cuti 0,5 hari + SP 1',
            self::CUT_LEAVE_ONE_DAY_AND_SP_1 => 'Potong cuti 1 hari + SP 1',
            self::CUT_LEAVE_ONE_HALF_DAY_AND_SP_1 => 'Potong cuti 1,5 hari + SP 1',
            self::CUT_LEAVE_TWO_DAY_AND_SP_1 => 'Potong cuti 2 hari + SP 1',
            self::CUT_LEAVE_TWO_HALF_DAY_AND_SP_1 => 'Potong cuti 2,5 hari + SP 1',
            self::CUT_LEAVE_THREE_DAY_AND_SP_1 => 'Potong cuti 3 hari + SP 1',
            self::CUT_LEAVE_THREE_HALF_DAY_AND_SP_1 => 'Potong cuti 3,5 hari + SP 1',
            self::CUT_LEAVE_FOUR_DAY_AND_SP_1 => 'Potong cuti 4 hari + SP 1',
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_1 => 'Potong cuti 4,5 hari + SP 1',
            self::CUT_LEAVE_FIVE_DAY_AND_SP_1 => 'Potong cuti 5 hari + SP 1',

            self::CUT_LEAVE_HALF_DAY_AND_SP_2 => 'Potong cuti 0,5 hari + SP 2',
            self::CUT_LEAVE_ONE_DAY_AND_SP_2 => 'Potong cuti 1 hari + SP 2',
            self::CUT_LEAVE_ONE_HALF_DAY_AND_SP_2 => 'Potong cuti 1,5 hari + SP 2',
            self::CUT_LEAVE_TWO_DAY_AND_SP_2 => 'Potong cuti 2 hari + SP 2',
            self::CUT_LEAVE_TWO_HALF_DAY_AND_SP_2 => 'Potong cuti 2,5 hari + SP 2',
            self::CUT_LEAVE_THREE_DAY_AND_SP_2 => 'Potong cuti 3 hari + SP 2',
            self::CUT_LEAVE_THREE_HALF_DAY_AND_SP_2 => 'Potong cuti 3,5 hari + SP 2',
            self::CUT_LEAVE_FOUR_DAY_AND_SP_2 => 'Potong cuti 4 hari + SP 2',
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_2 => 'Potong cuti 4,5 hari + SP 2',
            self::CUT_LEAVE_FIVE_DAY_AND_SP_2 => 'Potong cuti 5 hari + SP 2',

            self::CUT_LEAVE_HALF_DAY_AND_SP_3 => 'Potong cuti 0,5 hari + SP 3',
            self::CUT_LEAVE_ONE_DAY_AND_SP_3 => 'Potong cuti 1 hari + SP 3',
            self::CUT_LEAVE_ONE_HALF_DAY_AND_SP_3 => 'Potong cuti 1,5 hari + SP 3',
            self::CUT_LEAVE_TWO_DAY_AND_SP_3 => 'Potong cuti 2 hari + SP 3',
            self::CUT_LEAVE_TWO_HALF_DAY_AND_SP_3 => 'Potong cuti 2,5 hari + SP 3',
            self::CUT_LEAVE_THREE_DAY_AND_SP_3 => 'Potong cuti 3 hari + SP 3',
            self::CUT_LEAVE_THREE_HALF_DAY_AND_SP_3 => 'Potong cuti 3,5 hari + SP 3',
            self::CUT_LEAVE_FOUR_DAY_AND_SP_3 => 'Potong cuti 4 hari + SP 3',
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_3 => 'Potong cuti 4,5 hari + SP 3',
            self::CUT_LEAVE_FIVE_DAY_AND_SP_3 => 'Potong cuti 5 hari + SP 3',
            default => 'Toleransi',
        };
    }

    public function isSendWarningLetter(): bool
    {
        return !in_array($this, [self::TOLERANCE, self::NO_EXTRA_OFF]) && in_array($this, [
            self::LATE_WARNING_LETTER,
            self::LATE_WARNING_LETTER_AND_CALL_TO_HR,
            self::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
        ]);
    }

    public function isSendSPLetter(): bool
    {
        return !in_array($this, [self::TOLERANCE, self::NO_EXTRA_OFF]) && !in_array($this, [
            self::LATE_WARNING_LETTER,
            self::LATE_WARNING_LETTER_AND_CALL_TO_HR,
            self::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
            self::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
        ]);
    }
}
