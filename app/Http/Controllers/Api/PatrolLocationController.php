<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PatrolLocation\AttendRequest;
use App\Http\Requests\Api\PatrolLocation\ScanPatrolLocationRequest;
use App\Http\Requests\Api\PatrolLocation\ScanQrCodeRequest;
use App\Http\Requests\Api\PatrolLocation\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\ClientLocation;
use App\Models\Patrol;
use App\Models\PatrolLocation;
use Exception;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PatrolLocationController extends BaseController
{
    private Patrol $patrol;

    public function __construct()
    {
        parent::__construct();
        $this->patrol = Patrol::tenanted()->where('id', request()->segment(3))->firstOrFail(['id']);

        $this->middleware('permission:patrol_access', ['only' => ['restore']]);
        $this->middleware('permission:patrol_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:patrol_create', ['only' => 'store']);
        $this->middleware('permission:patrol_edit', ['only' => 'update']);
        $this->middleware('permission:patrol_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index(int $patrolId)
    {
        $data = QueryBuilder::for(PatrolLocation::where('patrol_id', $this->patrol->id)->with('clientLocation'))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('patrol_id'),
                AllowedFilter::exact('client_location_id'),
            ])
            ->allowedSorts([
                'id',
                'patrol_id',
                'client_location_id',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $patrolId, int $id)
    {
        $patrolLocation = $this->patrol->patrolLocations()->findOrFail($id);
        $patrolLocation->load(['patrol', 'clientLocation']);

        return new DefaultResource($patrolLocation);
    }

    public function store(int $patrolId, StoreRequest $request)
    {
        try {
            $patrolLocation = $this->patrol->patrolLocations()->create($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolLocation);
    }

    public function update(int $patrolId, int $id, StoreRequest $request)
    {
        $patrolLocation = $this->patrol->patrolLocations()->findOrFail($id);

        try {
            $patrolLocation->update($request->validated());
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($patrolLocation))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $patrolId, int $id)
    {
        $patrolLocation = $this->patrol->patrolLocations()->findOrFail($id);

        try {
            $patrolLocation->delete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function forceDelete(int $patrolId, $id)
    {
        $patrolLocation = $this->patrol->patrolLocations()->withTrashed()->findOrFail($id);

        try {
            $patrolLocation->forceDelete();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->deletedResponse();
    }

    public function restore(int $patrolId, $id)
    {
        $patrolLocation = $this->patrol->patrolLocations()->withTrashed()->findOrFail($id);

        try {
            $patrolLocation->restore();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($patrolLocation);
    }

    public function attend(AttendRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        $patrolLocation = PatrolLocation::where('id', $request->patrol_location_id)
            ->whereHas('patrol', function ($q) use ($user) {
                $q->whereDate('patrols.start_date', '<=', now());
                $q->whereDate('patrols.end_date', '>=', now());
                $q->whereHas('users', fn($q2) => $q2->where('user_patrols.user_id', $user->id));
            })->first();

        if (!$patrolLocation) {
            return $this->errorResponse('Invalid patrol location');
        }

        $userPatrolLocation = $user->userPatrolLocations()->firstWhere('patrol_location_id', $patrolLocation->id ?? null);

        if ($userPatrolLocation) {
            return $this->errorResponse('You have already attend at', ['date' => $userPatrolLocation->created_at]);
        }

        $userPatrolLocation = $user->userPatrolLocations()->create([
            'patrol_location_id' => $patrolLocation->id,
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return new DefaultResource($userPatrolLocation);
    }

    public function scanQrCode(ScanQrCodeRequest $request)
    {
        /** @var User $user */
        $user = auth('sanctum')->user();

        $splittedToken = explode(';', $request->token);
        $type = $splittedToken[0] ?? null;
        $uuid = $splittedToken[1] ?? null;

        $clientLocation = ClientLocation::firstWhere('uuid', $uuid);

        if (!$clientLocation) {
            return $this->errorResponse('Invalid token');
        }

        $patrolLocation = PatrolLocation::whereHas('clientLocation', fn($q) => $q->where('client_locations.uuid', $uuid))
            ->whereHas('patrol', function ($q) use ($user) {
                $q->whereDate('patrols.start_date', '<=', now());
                $q->whereDate('patrols.end_date', '>=', now());
                $q->whereHas('users', fn($q2) => $q2->where('user_patrols.user_id', $user->id));
            })->where(function ($q) use ($request) {
                if ($request->patrol_location_id) {
                    $q->where('patrol_locations.id', $request->patrol_location_id);
                }
            })->first();

        if (!$patrolLocation) {
            return $this->errorResponse('Invalid patrol location');
        }

        $userPatrolLocation = $user->userPatrolLocations()->firstWhere('patrol_location_id', $patrolLocation->id ?? null);

        if ($userPatrolLocation) {
            return new DefaultResource($userPatrolLocation);
            // return $this->errorResponse('You have already attend at', ['date' => $userPatrolLocation->created_at]);
        }

        $userPatrolLocation = $user->userPatrolLocations()->create([
            'patrol_location_id' => $patrolLocation->id,
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        return new DefaultResource($userPatrolLocation);
    }
}
