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
        // $mailClass = SP3Mail::class;
        $mailBody = <<<HTML
            <p>
                This letter serves as the Third and Final Warning regarding your repeated and unresolved tardiness, which has
                occurred consistently over the past five consecutive months, from __MONTHS__ of {{ date('Y') }}.
            </p>

            <p>
                Despite having been issued for all warning letter we have sent to you, there has been no meaningful improvement
                in your punctuality. Your continuous failure to report to work on time without valid or documented reasons
                constitutes a serious breach of the company's policies on attendance and discipline, as outlined in company's
                internal policies.
            </p>

            <p>
                Punctuality is a basic expectation and professional responsibility. Your ongoing tardiness negatively affects
                team dynamics, disrupts operational flow, and sets a poor example for others.
            </p>

            <p>
                This <strong>Third Warning Letter</strong> is the final stage in our disciplinary process. Please be advised
                that <strong>any further violation may result in immediate termination of your employment</strong>, in
                accordance with company policy and prevailing labor regulations.
            </p>

            <p>
                We strongly urge you to take this warning seriously and make immediate, consistent changes to your attendance
                behavior. Should you have any underlying issues affecting your punctuality, we encourage you to discuss them
                with your direct supervisor or HR as soon as possible.
            </p>
        HTML;

        $letterNumber = rand(100, 999) . '/HR-SP3/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_3,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 0.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_3,
                "total_cut_leave" => 1,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 1.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_3,
                "total_cut_leave" => 2,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 2.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_3,
                "total_cut_leave" => 3,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 3.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_3,
                "total_cut_leave" => 4,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_3,
                "total_cut_leave" => 4.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_3,
                "total_cut_leave" => 5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ]
        ];
    }

    private function month3Violation2Rule()
    {
        // $mailClass = SP2Mail::class;
        $mailBody = <<<HTML
            <p>
                This letter serves as a <strong>Second Warning</strong> regarding your ongoing pattern of tardiness, which has now extended over
                <strong>four consecutive months</strong> — specifically __MONTHS__ of {{ date('Y) }}.
            </p>

            <p>
                Despite a prior written warning, there has been no significant improvement in your punctuality. Continued delays
                in your arrival time without valid reasons constitute a violation of the company's rules and expectations
                regarding employee discipline and attendance.
            </p>

            <p>
                We would like to remind you that punctuality is critical to maintaining productivity and collaboration within
                the team. Repeated lateness not only impacts your own performance but also affects your colleagues and overall
                departmental operations
            </p>

            <p>
                This <strong>Second Warning Letter</strong> is intended to emphasize the seriousness of this matter. If there is no immediate and
                sustained improvement, the company may proceed with a <strong>Third (Final) Warning</strong> and consider <strong>termination of
                employment</strong>, in accordance with company policies and labor regulations.
            </p>

            <p>
                We urge you to treat this matter with the utmost seriousness and take immediate corrective action.
            </p>
        HTML;

        $letterNumber = rand(100, 999) . '/HR-SP2/IV/' . date('Y');
        return  [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_2,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 0.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_2,
                "total_cut_leave" => 1,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 1.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_2,
                "total_cut_leave" => 2,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 2.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_2,
                "total_cut_leave" => 3,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 3.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_2,
                "total_cut_leave" => 4,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_2,
                "total_cut_leave" => 4.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_2,
                "total_cut_leave" => 5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ]
        ];
    }

    private function month3Violation1Rule()
    {
        // $mailClass = SP1Mail::class;
        $mailBody = <<<HTML
            <p>
                We are writing to formally issue this <strong>First Warning Letter</strong> due to your continuous tardiness over the past three consecutive months, specifically during __MONTHS__ of {{ date("Y") }}.
            </p>

            <p>
                Despite having been issued for all warning letter we have sent to you, there has been no meaningful improvement
                in your punctuality. Your continuous failure to report to work on time without valid or documented reasons
                constitutes a serious breach of the company's policies on attendance and discipline, as outlined in company's
                internal policies.
            </p>

            <p>
                Punctuality is a basic expectation and professional responsibility. Your ongoing tardiness negatively affects
                team dynamics, disrupts operational flow, and sets a poor example for others.
            </p>

            <p>
                This <strong>Third Warning Letter</strong> is the final stage in our disciplinary process. Please be advised
                that <strong>any further violation may result in immediate termination of your employment</strong>, in
                accordance with company policy and prevailing labor regulations.
            </p>

            <p>
                We strongly urge you to take this warning seriously and make immediate, consistent changes to your attendance
                behavior. Should you have any underlying issues affecting your punctuality, we encourage you to discuss them
                with your direct supervisor or HR as soon as possible.
            </p>
        HTML;

        $letterNumber = rand(100, 999) . '/HR-SP1/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => '',
                // "mail_class" => null,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::SP_1,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 0.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_SP_1,
                "total_cut_leave" => 1,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 1.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_SP_1,
                "total_cut_leave" => 2,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 2.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_SP_1,
                "total_cut_leave" => 3,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 3.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_SP_1,
                "total_cut_leave" => 4,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_SP_1,
                "total_cut_leave" => 4.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_SP_1,
                "total_cut_leave" => 5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ]
        ];
    }

    private function month2Violation1Rule()
    {
        // $mailClass = WarningLetterTwoMail::class;
        $mailBody = ''; // mail body untuk warning leter cuma beda letter numbernya aja, sisanya sama. makanya langsung dibuat di view nya aja
        $letterNumber = rand(100, 999) . '/HR-WL2/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => null,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => null,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::LATE_WARNING_LETTER_AND_CALL_TO_HR,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 0.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ]
        ];
    }

    private function month1Violation1Rule()
    {
        // $mailClass = WarningLetterOneMail::class;
        $mailBody = ''; // mail body untuk warning leter cuma beda letter numbernya aja, sisanya sama. makanya langsung dibuat di view nya aja
        $letterNumber = rand(100, 999) . '/HR-WL1/IV/' . date('Y');
        return [
            [
                "start_minute" => 1,
                "end_minute" => 10,
                "type" => ReprimandType::TOLERANCE,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => null,
            ],
            [
                "start_minute" => 11,
                "end_minute" => 59,
                "type" => ReprimandType::NO_EXTRA_OFF,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => null,
            ],
            [
                "start_minute" => 60,
                "end_minute" => 119,
                "type" => ReprimandType::LATE_WARNING_LETTER,
                "total_cut_leave" => 0,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 120,
                "end_minute" => 239,
                "type" => ReprimandType::CUT_LEAVE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 0.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 240,
                "end_minute" => 359,
                "type" => ReprimandType::CUT_LEAVE_ONE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 360,
                "end_minute" => 479,
                "type" => ReprimandType::CUT_LEAVE_ONE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 1.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 480,
                "end_minute" => 599,
                "type" => ReprimandType::CUT_LEAVE_TWO_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 600,
                "end_minute" => 719,
                "type" => ReprimandType::CUT_LEAVE_TWO_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 2.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 720,
                "end_minute" => 839,
                "type" => ReprimandType::CUT_LEAVE_THREE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 840,
                "end_minute" => 959,
                "type" => ReprimandType::CUT_LEAVE_THREE_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 3.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 960,
                "end_minute" => 1079,
                "type" => ReprimandType::CUT_LEAVE_FOUR_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1080,
                "end_minute" => 1199,
                "type" => ReprimandType::CUT_LEAVE_FOUR_HALF_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 4.5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ],
            [
                "start_minute" => 1200,
                "end_minute" => 1319,
                "type" => ReprimandType::CUT_LEAVE_FIVE_DAY_AND_WARNING_LETTER,
                "total_cut_leave" => 5,
                "letter_number" => $letterNumber,
                "mail_body" => $mailBody,
                // "mail_class" => $mailClass,
            ]
        ];
    }
}
