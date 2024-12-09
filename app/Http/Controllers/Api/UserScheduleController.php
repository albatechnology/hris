<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserSchedule\StoreRequest;
use App\Http\Resources\Schedule\ScheduleResource;
use App\Models\Schedule;
use App\Models\User;
use Exception;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:schedule_edit');
    }

    public function index(int $scheduleId)
    {
        $schedule = Schedule::select('id')->tenanted()->where('id', $scheduleId)->firstOrFail();
        $data = QueryBuilder::for(User::tenanted()->whereHas('schedules', fn($q) => $q->where('schedule_id', $schedule->id)))
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name'
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return ScheduleResource::collection($data);
    }

    public function store(int $scheduleId, StoreRequest $request)
    {
        $schedule = Schedule::select('id')->tenanted()->where('id', $scheduleId)->firstOrFail();
        try {
            if ($request->user_ids && count($request->user_ids) > 0) {
                $schedule->users()->syncWithoutDetaching($request->user_ids);
            }
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new ScheduleResource($schedule);
    }

    public function destroy(int $scheduleId, User $user)
    {
        $schedule = Schedule::select('id')->tenanted()->where('id', $scheduleId)->firstOrFail();
        try {
            $schedule->users()->detach($user->id);
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return $this->deletedResponse();
    }
}
