<?php

namespace App\Services;

class LateService
{
    const TOLERANCE = 'tolerance';
    const NO_EXTRA_OFF = 'no_extra_off';
    const LATE_WARNING_LETTER = 'late_warning_letter';
    const LATE_WARNING_LETTER_AND_CALL_TO_HR = 'late_warning_letter_and_call_to_HR';
    const SP_1 = 'SP_1';
    const SP_2 = 'SP_2';
    const SP_3 = 'SP_3';
    const CUT_LEAVE_AND_WARNING_LETTER = 'cut_leave_and_warning_letter';
    const CUT_LEAVE_AND_SP_1 = 'cut_leave_and_SP_1';
    const CUT_LEAVE_AND_SP_2 = 'cut_leave_and_SP_2';
    const CUT_LEAVE_AND_SP_3 = 'cut_leave_and_SP_3';

    public function rules(): array
    {
        return [
            "month_1_violation_1" => [
                [
                    "start_minute" => 1,
                    "end_minute" => 10,
                    "type" => self::TOLERANCE,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 11,
                    "end_minute" => 59,
                    "type" => self::NO_EXTRA_OFF,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 60,
                    "end_minute" => 119,
                    "type" => self::LATE_WARNING_LETTER,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 120,
                    "end_minute" => 239,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 0.5,
                ],
                [
                    "start_minute" => 240,
                    "end_minute" => 359,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 1,
                ],
                [
                    "start_minute" => 360,
                    "end_minute" => 479,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 1.5,
                ],
                [
                    "start_minute" => 480,
                    "end_minute" => 599,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 2,
                ],
                [
                    "start_minute" => 600,
                    "end_minute" => 719,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 2.5,
                ],
                [
                    "start_minute" => 720,
                    "end_minute" => 839,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 3,
                ],
                [
                    "start_minute" => 840,
                    "end_minute" => 959,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 3.5,
                ],
                [
                    "start_minute" => 960,
                    "end_minute" => 1079,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 4,
                ],
                [
                    "start_minute" => 1080,
                    "end_minute" => 1199,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 4.5,
                ],
                [
                    "start_minute" => 1200,
                    "end_minute" => 1319,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 5,
                ]
            ],
            "month_2_violation_1" => [
                [
                    "start_minute" => 1,
                    "end_minute" => 10,
                    "type" => self::TOLERANCE,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 11,
                    "end_minute" => 59,
                    "type" => self::NO_EXTRA_OFF,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 60,
                    "end_minute" => 119,
                    "type" => self::LATE_WARNING_LETTER_AND_CALL_TO_HR,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 120,
                    "end_minute" => 239,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 0.5,
                ],
                [
                    "start_minute" => 240,
                    "end_minute" => 359,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 1,
                ],
                [
                    "start_minute" => 360,
                    "end_minute" => 479,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 1.5,
                ],
                [
                    "start_minute" => 480,
                    "end_minute" => 599,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 2,
                ],
                [
                    "start_minute" => 600,
                    "end_minute" => 719,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 2.5,
                ],
                [
                    "start_minute" => 720,
                    "end_minute" => 839,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 3,
                ],
                [
                    "start_minute" => 840,
                    "end_minute" => 959,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 3.5,
                ],
                [
                    "start_minute" => 960,
                    "end_minute" => 1079,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 4,
                ],
                [
                    "start_minute" => 1080,
                    "end_minute" => 1199,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 4.5,
                ],
                [
                    "start_minute" => 1200,
                    "end_minute" => 1319,
                    "type" => self::CUT_LEAVE_AND_WARNING_LETTER,
                    "total_cut_leave" => 5,
                ]
            ],
            "month_3_violation_1" => [
                [
                    "start_minute" => 1,
                    "end_minute" => 10,
                    "type" => self::TOLERANCE,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 11,
                    "end_minute" => 59,
                    "type" => self::NO_EXTRA_OFF,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 60,
                    "end_minute" => 119,
                    "type" => self::SP_1,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 120,
                    "end_minute" => 239,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 0.5,
                ],
                [
                    "start_minute" => 240,
                    "end_minute" => 359,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 1,
                ],
                [
                    "start_minute" => 360,
                    "end_minute" => 479,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 1.5,
                ],
                [
                    "start_minute" => 480,
                    "end_minute" => 599,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 2,
                ],
                [
                    "start_minute" => 600,
                    "end_minute" => 719,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 2.5,
                ],
                [
                    "start_minute" => 720,
                    "end_minute" => 839,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 3,
                ],
                [
                    "start_minute" => 840,
                    "end_minute" => 959,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 3.5,
                ],
                [
                    "start_minute" => 960,
                    "end_minute" => 1079,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 4,
                ],
                [
                    "start_minute" => 1080,
                    "end_minute" => 1199,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 4.5,
                ],
                [
                    "start_minute" => 1200,
                    "end_minute" => 1319,
                    "type" => self::CUT_LEAVE_AND_SP_1,
                    "total_cut_leave" => 5,
                ]
            ],
            "month_3_violation_2" => [
                [
                    "start_minute" => 1,
                    "end_minute" => 10,
                    "type" => self::TOLERANCE,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 11,
                    "end_minute" => 59,
                    "type" => self::NO_EXTRA_OFF,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 60,
                    "end_minute" => 119,
                    "type" => self::SP_2,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 120,
                    "end_minute" => 239,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 0.5,
                ],
                [
                    "start_minute" => 240,
                    "end_minute" => 359,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 1,
                ],
                [
                    "start_minute" => 360,
                    "end_minute" => 479,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 1.5,
                ],
                [
                    "start_minute" => 480,
                    "end_minute" => 599,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 2,
                ],
                [
                    "start_minute" => 600,
                    "end_minute" => 719,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 2.5,
                ],
                [
                    "start_minute" => 720,
                    "end_minute" => 839,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 3,
                ],
                [
                    "start_minute" => 840,
                    "end_minute" => 959,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 3.5,
                ],
                [
                    "start_minute" => 960,
                    "end_minute" => 1079,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 4,
                ],
                [
                    "start_minute" => 1080,
                    "end_minute" => 1199,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 4.5,
                ],
                [
                    "start_minute" => 1200,
                    "end_minute" => 1319,
                    "type" => self::CUT_LEAVE_AND_SP_2,
                    "total_cut_leave" => 5,
                ]
            ],
            "month_3_violation_3" => [
                [
                    "start_minute" => 1,
                    "end_minute" => 10,
                    "type" => self::TOLERANCE,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 11,
                    "end_minute" => 59,
                    "type" => self::NO_EXTRA_OFF,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 60,
                    "end_minute" => 119,
                    "type" => self::SP_3,
                    "total_cut_leave" => 0,
                ],
                [
                    "start_minute" => 120,
                    "end_minute" => 239,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 0.5,
                ],
                [
                    "start_minute" => 240,
                    "end_minute" => 359,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 1,
                ],
                [
                    "start_minute" => 360,
                    "end_minute" => 479,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 1.5,
                ],
                [
                    "start_minute" => 480,
                    "end_minute" => 599,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 2,
                ],
                [
                    "start_minute" => 600,
                    "end_minute" => 719,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 2.5,
                ],
                [
                    "start_minute" => 720,
                    "end_minute" => 839,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 3,
                ],
                [
                    "start_minute" => 840,
                    "end_minute" => 959,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 3.5,
                ],
                [
                    "start_minute" => 960,
                    "end_minute" => 1079,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 4,
                ],
                [
                    "start_minute" => 1080,
                    "end_minute" => 1199,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 4.5,
                ],
                [
                    "start_minute" => 1200,
                    "end_minute" => 1319,
                    "type" => self::CUT_LEAVE_AND_SP_3,
                    "total_cut_leave" => 5,
                ]
            ]
        ];
    }
}
