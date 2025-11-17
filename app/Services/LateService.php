<?php

namespace App\Services;

use function PHPUnit\Framework\returnSelf;

class LateService
{
    const TOLERANCE = 'tolerance';
    const NO_EXTRA_OFF = 'no_extra_off';
    const LATE_WARNING_LETTER = 'late_warning_letter';
    const LATE_WARNING_LETTER_AND_CALL_TO_HR = 'late_warning_letter_and_call_to_HR';
    const SP_1 = 'SP 1';
    const SP_2 = 'SP 2';
    const SP_3 = 'SP 3';
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

    public const MINUTES_TRESHOLD = 10;

    public function resolveRulesetKey(int $stage, int $violation = 1): string
    {
        $rules = $this->rules();
        $preferred = "month_{$stage}_violation";
        if (isset($rules[$preferred])) return $preferred;

        $fallback = "month_{$stage}_violation_1";
        if (isset($rules[$fallback])) return $fallback;

        return "month_1_violation_1";
    }

    public function maxStage(): int
    {
        $max = 1;
        foreach (array_keys($this->rules()) as $key) {
            if (preg_match('/^month_(\d+)_violation_\d+$/', $key, $m)) {
                $max = max($max, (int)$m[1]);
            }
        }
        return $max;
    }

    public function evaluatePenalty(int $totalMinutes, int $stage, int $violation = 1):array
    {
        $maxStage = $this->maxStage();
        $stage = max(1, min($stage, $maxStage));

        $rulesetKey = $this->resolveRulesetKey($stage,$violation);
        $rule = $this->findRuleForMinutes($totalMinutes, $rulesetKey);

        $type = $rule['type'] ?? null;
        $cut = $rule['total_cut_leave'] ?? null;
        $message = self::messageFor($type, $cut);

        return [
            'ruleset_key' => $rulesetKey,
            'rule' => $rule,
            'type' => $type,
            'cut' => $cut,
            'message' => $message
        ];
    }

    /**
     * Return the matching rule entry for given total minutes and ruleset key.
     *
     * @param int $minutes
     * @param string $setKey
     * @return array|null
     */
    public function findRuleForMinutes(int $minutes, string $setKey = 'month_1_violation_1'): ?array
    {
        $rules = $this->rules();
        if (!isset($rules[$setKey]) || !is_array($rules[$setKey])) return null;

        foreach ($rules[$setKey] as $entry) {
            if ($minutes >= $entry['start_minute'] && $minutes <= $entry['end_minute']) {
                return $entry;
            }
        }

        return null;
    }

    public static function messages(): array
    {
        return [
            self::TOLERANCE                           => 'Terlambat masih dalam toleransi. Tidak ada tindakan.',
            self::NO_EXTRA_OFF                        => 'Tidak mendapat Extra Off.',
            self::LATE_WARNING_LETTER                 => 'Jangan telat lagi! (Surat teguran).',
            self::LATE_WARNING_LETTER_AND_CALL_TO_HR  => 'Yang bersangkutan wajib menghadap HRD!',
            self::SP_1                                => 'Surat Peringatan 1 (SP1).',
            self::SP_2                                => 'Surat Peringatan 2 (SP2).',
            self::SP_3                                => 'Surat Peringatan 3 (SP3).',
            self::CUT_LEAVE_AND_WARNING_LETTER        => 'Pemotongan cuti :cut hari dan surat teguran.',
            self::CUT_LEAVE_AND_SP_1                  => 'Pemotongan cuti :cut hari dan SP1.',
            self::CUT_LEAVE_AND_SP_2                  => 'Pemotongan cuti :cut hari dan SP2.',
            self::CUT_LEAVE_AND_SP_3                  => 'Pemotongan cuti :cut hari dan SP3.',
        ];
    }

    public static function messageFor(?string $type, ?float $cutLeave = null): string
    {
        if (!$type) return '';
        $map = self::messages();
        $msg = $map[$type] ?? '';
        if ($msg === '') return '';
        // render placeholder :cut jika ada informasi potong cuti
        if (str_contains($msg, ':cut')) {
            $cut = $cutLeave ?? 0;
            // format 0.5 / 1 / 2.5 tanpa trailing zero berlebih
            $cutStr = rtrim(rtrim(number_format($cut, 2, '.', ''), '0'), '.');
            $msg = str_replace(':cut', $cutStr, $msg);
        }
        return $msg;
    }
}
