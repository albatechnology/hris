<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Panic\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Panic;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use App\Enums\PanicStatus;
use App\Http\Requests\Api\Panic\UpdateRequest;

class PanicController extends BaseController
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
        $data = QueryBuilder::for(Panic::tenanted()->with('user'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'user_id',
                'status',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(Panic $panic)
    {
        return new DefaultResource($panic->load('user'));
    }

    public function store(StoreRequest $request)
    {
        try {
            $panic = auth('sanctum')->user()->panics()->create([
                'company_id' => $request->company_id,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'status' => PanicStatus::PANIC,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($panic->load('user'));
    }

    public function update(Panic $panic, UpdateRequest $request)
    {
        try {
            $panic->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($panic))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Panic $panic)
    {
        try {
            $panic->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $panic = Panic::withTrashed()->findOrFail($id);

        try {
            $panic->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $panic = Panic::withTrashed()->findOrFail($id);

        try {
            $panic->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($panic);
    }
}
