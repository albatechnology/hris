<?php

namespace App\Imports;

use App\Enums\ApprovalStatus;
use App\Models\RequestShift;
use App\Models\Shift;
use App\Models\User;
use App\Services\ScheduleService;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class ShiftUsersImport implements ToCollection
{
    public function __construct(protected User $user) {}
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        $today = date('Y-m-d');
        $header = $rows[0]->forget([0, 1])->values();
        unset($rows[0]);

        foreach ($rows as $row) {
            /** @var User $user */
            $user = User::where('nik', $row[0])->first(['id']);
            $row = $row->forget([0, 1])->values();

            foreach ($header as $key => $value) {
                if ($shiftName = $row[$key]) {
                    $shiftName = str_replace('–', '-', trim($shiftName));
                    $shift = Shift::where(fn($q) => $q->tenanted()->orWhereNull('company_id'))->where('name', $shiftName)->first(['id', 'name']);

                    if ($shift) {
                        if ($today >= $value) {
                            // if change shift for today and before, just update shift_id on user attendance
                            $attendance = $user->attendances()->where('date', $value)->first();

                            if ($attendance) {
                                $attendance->update([
                                    'shift_id' => $shift->id
                                ]);
                            } else {
                                $this->createRequestShift($user, $shift, $value);
                            }
                        } else {
                            $this->createRequestShift($user, $shift, $value);
                        }
                    }
                }
            }
        }
    }

    public function createRequestShift(User $user, Shift $shift, string $value)
    {
        $todaySchedule = ScheduleService::getTodaySchedule($user, $value, ['id'], ['id']);
        if ($todaySchedule && $todaySchedule->shift) {
            $description = 'Import Shift by ' . $this->user->name . ' - AUTO APPROVED';
            try {
                // delete request existing request shift
                RequestShift::where('user_id', $user->id)->where('date', $value)->delete();

                $requestShift = new RequestShift();
                $requestShift->user_id = $user->id;
                $requestShift->schedule_id = $todaySchedule->id;
                $requestShift->old_shift_id = $todaySchedule->shift->id;
                $requestShift->new_shift_id = $shift->id;
                $requestShift->date = $value;
                $requestShift->description = $description;
                $requestShift->created_by = $this->user->id;
                $requestShift->saveQuietly();

                // auto approved by uploader
                $requestShift->approvals()->createQuietly([
                    'user_id' => $this->user->id,
                    'approval_status' => ApprovalStatus::APPROVED,
                    'approved_at' => now(),
                    'description' => $description,
                ]);
            } catch (Exception $e) {
                throw $e;
            }
        }
    }
}
