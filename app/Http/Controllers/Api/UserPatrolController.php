<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DefaultResource;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Models\UserPatrol;
use Carbon\Carbon;

class UserPatrolController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:panic_access', ['only' => ['restore']]);
        // $this->middleware('permission:panic_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:panic_create', ['only' => 'store']);
        // $this->middleware('permission:panic_edit', ['only' => 'update']);
        // $this->middleware('permission:panic_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(
            UserPatrol::with([
                'user.detail',
                'patrol.branch'
            ])->whereHas('patrol', function ($q) {
                $q->whereDate('patrols.start_date', '<=', now());
                $q->whereDate('patrols.end_date', '>=', now());
                $q->whereHas('branch', fn($q2) => $q2->tenanted());
                // $q->whereDoesntHave('tasks', function($q2){
                //   $q2->where('status', PatrolTaskStatus::PENDING);
                // });
            })
            // ->has('user.userPatrolLocations')
        )->allowedFilters([
            AllowedFilter::exact('user_id'),
            AllowedFilter::exact('patrol_id'),
            AllowedFilter::callback('last_detected', function ($query, $value) {
                $query->whereHas('user.detail', function ($q) use ($value) {
                    $q->where('user_details.detected_at', '>=', Carbon::now()->subMinutes($value)->toDateTimeString());
                });
            }),
            AllowedFilter::callback('branch_id', function ($query, $value) {
                $query->whereHas('patrol', fn($q) => $q->where('branch_id', $value));
            }),
        ])->allowedSorts([
            'id',
            'patrol_id',
            'user_id',
            'created_at',
        ])->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
