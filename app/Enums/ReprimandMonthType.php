<?php

namespace App\Enums;

enum ReprimandMonthType: string
{
    use BaseEnum;

    case MONTH_1_VIOLATION_1 = 'month_1_violation_1';
    case MONTH_2_VIOLATION_1 = 'month_2_violation_1';
    case MONTH_3_VIOLATION_1 = 'month_3_violation_1';
    case MONTH_3_VIOLATION_2 = 'month_3_violation_2';
    case MONTH_3_VIOLATION_3 = 'month_3_violation_3';

    /**
     * Urutan enum
     */
    private static function ordered(): array
    {
        return [
            self::MONTH_1_VIOLATION_1,
            self::MONTH_2_VIOLATION_1,
            self::MONTH_3_VIOLATION_1,
            self::MONTH_3_VIOLATION_2,
            self::MONTH_3_VIOLATION_3,
        ];
    }

    /**
     * Ambil enum berikutnya (next)
     */
    public function next(): ?self
    {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);

        return $ordered[$index + 1] ?? null;
    }

    /**
     * Ambil enum sebelumnya (previous)
     */
    public function previous(): ?self
    {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);

        return $ordered[$index - 1] ?? null;
    }

    public function getAllPreviousMonths()
    {
        $repMonthTypes = [];
        $pm = $this;
        while (!is_null($pm)) {
            $repMonthTypes[] = $pm;
            $pm = $pm->previous();
        }

        return $repMonthTypes;
    }

    public function getRules(): array
    {
        return match ($this) {
            self::MONTH_1_VIOLATION_1 => $this->month1Violation1Rule(),
            self::MONTH_2_VIOLATION_1 => $this->month2Violation1Rule(),
            self::MONTH_3_VIOLATION_1 => $this->month3Violation1Rule(),
            self::MONTH_3_VIOLATION_2 => $this->month3Violation2Rule(),
            self::MONTH_3_VIOLATION_3 => $this->month3Violation3Rule(),
            default => [],
        };
    }

    public function getReprimandType(int $lateMinute): ReprimandType
    {
        $rules = $this->getRules();

        foreach ($rules as $rule) {
            if ($lateMinute >= $rule['start_minute'] && $lateMinute <= $rule['end_minute']) {
                return $rule['type'];
            }
        }

        // kalau nggak ketemu, ambil rule terakhir
        $lastRule = end($rules);
        return $lastRule['type'];
    }

    public function getRule(ReprimandType $reprimandType): array
    {
        $rules = $this->getRules();

        foreach ($rules as $rule) {
            if ($rule['type'] === $reprimandType) {
                return $rule;
            }
        }

        return end($rules); // fallback ke rule terakhir
    }

    private function month3Violation3Rule()
    {
        $letterTitle = "THIRD (FINAL) WARNING LETTER";
        $letterSubject = "Third and Final Warning - Continued Tardiness";
        $letterNumber = rand(100, 999) . '/HR-SP3/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_3,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 0.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_3,
                "total_cut_leave" => 1,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 1.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_3,
                "total_cut_leave" => 2,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 2.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_3,
                "total_cut_leave" => 3,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 3.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_3,
                "total_cut_leave" => 4,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 4.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_3,
                "total_cut_leave" => 5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ]
        ];
    }

    private function month3Violation2Rule()
    {
        $letterTitle = "SECOND WARNING LETTER";
        $letterSubject = "Second Warning Letter - Continued Tardiness";
        $letterNumber = rand(100, 999) . '/HR-SP2/IV/' . date('Y');
        return  [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_2,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 0.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_2,
                "total_cut_leave" => 1,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 1.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_2,
                "total_cut_leave" => 2,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 2.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_2,
                "total_cut_leave" => 3,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 3.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_2,
                "total_cut_leave" => 4,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 4.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_2,
                "total_cut_leave" => 5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ]
        ];
    }

    private function month3Violation1Rule()
    {
        $letterTitle = "FIRST WARNING LETTER";
        $letterSubject = "First Warning Letter – Repeated Tardiness";
        $letterNumber = rand(100, 999) . '/HR-SP1/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_1,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 0.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_1,
                "total_cut_leave" => 1,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 1.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_1,
                "total_cut_leave" => 2,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 2.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_1,
                "total_cut_leave" => 3,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 3.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_1,
                "total_cut_leave" => 4,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 4.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_1,
                "total_cut_leave" => 5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ]
        ];
    }

    private function month2Violation1Rule()
    {
        $letterTitle = "";
        $letterSubject = "";
        $letterNumber = rand(100, 999) . '/HR-WL2/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::LATE_WARNING_LETTER_AND_CALL_TO_HR,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 0.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ]
        ];
    }

    private function month1Violation1Rule()
    {
        $letterTitle = "";
        $letterSubject = "";
        $letterNumber = rand(100, 999) . '/HR-WL1/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::LATE_WARNING_LETTER,
                "total_cut_leave" => 0,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 0.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4.5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 5,
                "letter_title" => $letterTitle,
                "letter_subject" => $letterSubject,
                "letter_number" => $letterNumber,
            ]
        ];
    }
}
