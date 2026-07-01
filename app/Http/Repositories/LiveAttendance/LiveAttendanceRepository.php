<?php

namespace App\Http\Repositories\LiveAttendance;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\LiveAttendance\LiveAttendanceRepositoryInterface;
use App\Models\LiveAttendance;
use Closure;
use Illuminate\Database\Eloquent\Model;

class LiveAttendanceRepository extends BaseRepository implements LiveAttendanceRepositoryInterface
{
    public function __construct(LiveAttendance $model)
    {
        parent::__construct($model);
    }

    public function createWithRelations(array $data, array $locations = [], array $userIds = [])
    {
        $liveAttendance = $this->model::create($data);

        if (!empty($locations)) {
            $liveAttendance->locations()->createMany($locations);
        }

        if (!empty($userIds)) {
            \App\Models\User::whereIn('id', $userIds)->update(['live_attendance_id' => $liveAttendance->id]);
        }

        return $liveAttendance;
    }

    public function updateWithRelations(int|string $id, array $data, array $locations = [], array $userIds = [])
    {
        $liveAttendance = $this->query()->findOrFail((int) $id);

        $liveAttendance->update($data);

        $liveAttendance->locations()->delete();
        if (!$liveAttendance->is_flexible) {
            if (!empty($locations)) {
                $liveAttendance->locations()->createMany($locations);
            }
        }

        $oldUserIds = $liveAttendance->users()->pluck('id')->toArray();
        if (!empty($userIds)) {
            \App\Models\User::whereIn('id', $userIds)->update(['live_attendance_id' => $liveAttendance->id]);
            if (!empty($oldUserIds)) {
                \App\Models\User::whereIn('id', $oldUserIds)->whereNotIn('id', $userIds)->update(['live_attendance_id' => null]);
            }
        } elseif (!empty($oldUserIds)) {
            \App\Models\User::whereIn('id', $oldUserIds)->update(['live_attendance_id' => null]);
        }

        return $liveAttendance;
    }
}
