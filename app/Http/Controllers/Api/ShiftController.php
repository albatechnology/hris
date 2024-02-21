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
        // $this->middleware('permission:shift_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:shift_access', ['only' => ['restore']]);
        $this->middleware('permission:shift_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:shift_create', ['only' => 'store']);
        $this->middleware('permission:shift_edit', ['only' => 'update']);
        $this->middleware('permission:shift_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Shift::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name', 'clock_in', 'clock_out',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'name', 'clock_in', 'clock_out', 'created_at',
            ])
            ->paginate($this->per_page);

        return ShiftResource::collection($data);
    }

    public function show(Shift $shift)
    {
        return new ShiftResource($shift);
    }

    public function store(StoreRequest $request)
    {
        $shift = Shift::create($request->validated());

        return new ShiftResource($shift);
    }

    public function update(Shift $shift, StoreRequest $request)
    {
        $shift->update($request->validated());

        return (new ShiftResource($shift))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Shift $shift)
    {
        $shift->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $shift = Shift::withTrashed()->findOrFail($id);
        $shift->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $shift = Shift::withTrashed()->findOrFail($id);
        $shift->restore();

        return new ShiftResource($shift);
    }
}
