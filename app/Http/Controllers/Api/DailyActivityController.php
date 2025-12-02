<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\DailyActivity\StoreRequest;
use App\Http\Requests\Api\DailyActivity\ExportRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\DailyActivity\DailyActivityServiceInterface;
use App\Models\DailyActivity;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class DailyActivityController extends BaseController
{
    public function __construct(protected DailyActivityServiceInterface $service)
    {
        parent::__construct();
        // $this->middleware('permission:daily_activity_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:daily_activity_create', ['only' => 'store']);
        // $this->middleware('permission:daily_activity_edit', ['only' => 'update']);
        // $this->middleware('permission:daily_activity_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(DailyActivity::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('company_id', 'whereCompanyId'),
                AllowedFilter::scope('branch_id', 'whereBranchId'),
                AllowedFilter::scope('start_at', 'startAt'),
                AllowedFilter::scope('end_at', 'endAt'),
                'title',
            ])
            ->allowedIncludes(
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name');
                }),
            )
            ->allowedSorts([
                'id',
                'user_id',
                'title',
                'start_at',
                'end_at',
                'created_at',
            ])
            ->with(['media'])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $activity = $this->service->findById($id);
        if (!$activity) {
            return $this->errorResponse('Daily Activity not found', code: 404);
        }

        return new DefaultResource($activity->load([
            'user' => fn($q) => $q->select('id', 'name'),
            'media',
        ]));
    }

    public function store(StoreRequest $request)
    {
        $this->service->create($request->validated());
        return $this->createdResponse();
    }

    // public function update(int $id, UpdateRequest $request)
    // {
    //     if (!$this->service->update($id, $request->validated())) {
    //         return $this->errorResponse('Daily Activity not found', code: 404);
    //     }

    //     return $this->updatedResponse();
    // }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function export(ExportRequest $request)
    {
        $startAt = $request->filter['start_at'];
        $endAt = $request->filter['end_at'];

        $userIds = isset($request['filter']['user_ids']) && !empty($request['filter']['user_ids']) ? explode(',', $request['filter']['user_ids']) : null;
        $dailyActivities = DailyActivity::query()
            ->startAt($startAt)->endAt($endAt)
            ->when($request->filter['company_id'] ?? null, fn($q) => $q->whereHas('user', fn($q2) => $q2->where('company_id', $request->filter['company_id'])))
            ->when($request->filter['branch_id'] ?? null, fn($q) => $q->whereHas('user', fn($q2) => $q2->where('branch_id', $request->filter['branch_id'])))
            ->when($userIds, fn($q) => $q->whereIn('user_id', $userIds))
            ->with([
                'user' => fn($q) => $q->select('id', 'name'),
                'media',
            ])
            ->get();

        $html = view('api.exports.daily-activity.daily-activity', compact('dailyActivities', 'startAt', 'endAt'))->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="daily activity ' . $startAt . ' to ' . $endAt . '.xls"')
            ->header('Cache-Control', 'max-age=0');
    }
}
