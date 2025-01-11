<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Shift\StoreRequest;
use App\Http\Resources\Shift\ShiftResource;
use App\Models\Shift;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ShiftController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:shift_access', ['only' => ['restore']]);
        $this->middleware('permission:shift_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:shift_create', ['only' => 'store']);
        $this->middleware('permission:shift_edit', ['only' => 'update']);
        $this->middleware('permission:shift_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Shift::tenanted()->orWhereNull('company_id'))
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'type',
                'clock_in',
                'clock_out',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'clock_in',
                'clock_out',
                'created_at',
            ])
            ->paginate($this->per_page);

        return ShiftResource::collection($data);
    }

    public function show(int $id)
    {
        $shift = Shift::findTenanted($id);
        return new ShiftResource($shift);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_department_ids'] = $request->department_ids;
        $data['show_in_request_position_ids'] = $request->position_ids;
        $shift = Shift::create($data);

        return new ShiftResource($shift);
    }

    public function update(int $id, StoreRequest $request)
    {
        $shift = Shift::findTenanted($id);

        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_department_ids'] = $request->department_ids;
        $data['show_in_request_position_ids'] = $request->position_ids;
        $shift->update($data);

        return (new ShiftResource($shift))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $shift = Shift::findTenanted($id);
        $shift->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $shift = Shift::withTrashed()->tenanted()->where('id', $id)->fisrtOrFail();
        $shift->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $shift = Shift::withTrashed()->tenanted()->where('id', $id)->fisrtOrFail();
        $shift->restore();

        return new ShiftResource($shift);
    }
}
