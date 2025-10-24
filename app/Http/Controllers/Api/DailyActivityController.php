<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\DailyActivity\StoreRequest;
use App\Http\Requests\Api\DailyActivity\UpdateRequest;
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
                AllowedFilter::scope('created_start_date', 'createdAtStart'),
                AllowedFilter::scope('created_end_date', 'createdAtEnd'),
                'description',
            ])
            ->allowedIncludes(
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name');
                }),
            )
            ->allowedSorts([
                'id',
                'user_id',
                'description',
                'created_at',
            ])
            ->with(['media'])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $activity = $this->service->findById($id);
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

    public function update(int $id, UpdateRequest $request)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());
        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);

        return $this->deletedResponse();
    }
}
