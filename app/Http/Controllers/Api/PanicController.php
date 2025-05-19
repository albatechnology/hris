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
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Panic\PanicNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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
                AllowedFilter::exact('client_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('status'),
            ])
            ->allowedSorts([
                'id',
                'client_id',
                'user_id',
                'status',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $panic = Panic::findTenanted($id);
        return new DefaultResource($panic->load('user', 'client'));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $panic = auth('sanctum')->user()->panics()->create([
                'client_id' => $request->client_id,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'status' => PanicStatus::PANIC,
            ]);

            $supervisors = User::whereIn('id', $panic->user->supervisors?->pluck('supervisor_id'))->get();
            if ($supervisors->count() == 0) {
                $supervisors = User::where('id', Setting::where('key', 'request_approver')->where('company_id', $panic->user?->company_id)->first()?->value)->get();
            }

            if ($supervisors->count()) {
                Notification::sendNow($supervisors, new PanicNotification($panic));
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($panic->load('user'));
    }

    public function update(int $id, UpdateRequest $request)
    {
        $panic = Panic::findTenanted($id);
        try {
            $panic->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($panic))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $panic = Panic::findTenanted($id);
        try {
            $panic->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $panic = Panic::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        try {
            $panic->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $panic = Panic::withTrashed()->tenanted()->where('id', $id)->firstOrFail();

        try {
            $panic->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($panic);
    }

    public function myPanic()
    {
        $panic = auth('sanctum')->user()->panics()->with('user')->where('status', PanicStatus::PANIC)->get();
        return new DefaultResource($panic);
    }
}
