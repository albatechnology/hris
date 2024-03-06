<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserSchedule\StoreRequest;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Models\Schedule;
use App\Models\User;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserScheduleController extends BaseController
{
    public function index(Schedule $schedule)
    {
        $data = QueryBuilder::for(User::tenanted()->whereHas('schedules', fn ($q) => $q->where('schedule_id', $schedule->id)))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name', 'effective_date',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'effective_date', 'created_at',
            ])
            ->paginate($this->per_page);

        return ScheduleResource::collection($data);
    }

    public function store(Schedule $schedule, StoreRequest $request)
    {
        try {
            if ($request->user_ids && count($request->user_ids) > 0) {
                $schedule->users()->syncWithoutDetaching($request->user_ids);
            }
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new ScheduleResource($schedule);
    }

    public function destroy(Schedule $schedule, User $user)
    {
        try {
            $schedule->users()->detach($user->id);
        } catch (\Throwable $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
