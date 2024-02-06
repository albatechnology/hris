<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Overtime\StoreRequest;
use App\Http\Resources\Overtime\OvertimeResource;
use App\Models\Overtime;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OvertimeController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('permission:overtime_access', ['only' => ['restore']]);
        $this->middleware('permission:overtime_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:overtime_create', ['only' => 'store']);
        $this->middleware('permission:overtime_edit', ['only' => 'update']);
        $this->middleware('permission:overtime_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Overtime::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'is_rounding', 'compensation_rate_per_day', 'rate_type', 'rate_amount', 'created_at'
            ])
            ->paginate($this->per_page);

        return OvertimeResource::collection($data);
    }

    public function show(Overtime $overtime)
    {
        $data = QueryBuilder::for(Overtime::findTenanted($overtime->id))
            ->allowedIncludes(['company'])
            ->firstOrFail();

        return new OvertimeResource($data);
    }

    public function store(StoreRequest $request)
    {
        $overtime = Overtime::create($request->validated());

        return new OvertimeResource($overtime);
    }

    public function update(Overtime $overtime, StoreRequest $request)
    {
        $overtime->update($request->validated());

        return (new OvertimeResource($overtime))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Overtime $overtime)
    {
        $overtime->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $overtime = Overtime::withTrashed()->findOrFail($id);
        $overtime->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $overtime = Overtime::withTrashed()->findOrFail($id);
        $overtime->restore();
        return new OvertimeResource($overtime);
    }
}
