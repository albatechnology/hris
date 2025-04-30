<?php

namespace App\Http\Controllers\Api;

use App\Enums\NotificationType;
use App\Http\Requests\Api\Reprimand\StoreRequest;
use App\Http\Requests\Api\Reprimand\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Reprimand;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class ReprimandController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:reprimand_access', ['only' => ['restore']]);
        $this->middleware('permission:reprimand_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:reprimand_create', ['only' => 'store']);
        $this->middleware('permission:reprimand_edit', ['only' => 'update']);
        $this->middleware('permission:reprimand_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Reprimand::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('assign_to'),
                'type',
                'effective_date',
                'end_date',
            ])
            ->allowedIncludes([
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name', 'nik', 'email');
                }),
                AllowedInclude::callback('assignTo', function ($query) {
                    $query->select('id', 'name', 'nik', 'email');
                }),
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'assign_to',
                'type',
                'effective_date',
                'end_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $reprimand = Reprimand::findTenanted($id);
        return new DefaultResource($reprimand->loadMissing([
            'user' => fn($q) => $q->select('id', 'name', 'nik', 'email'),
            'assignTo' => fn($q) => $q->select('id', 'name', 'nik', 'email'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $reprimand = Reprimand::create($request->validated());

            $notificationType = NotificationType::REPRIMAND;
            $reprimand->user->notify(new ($notificationType->getNotificationClass())($notificationType, $reprimand));
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($reprimand);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $reprimand = Reprimand::findTenanted($id);

        $reprimand->update($request->validated());

        return (new DefaultResource($reprimand))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $reprimand = Reprimand::findTenanted($id);
        $reprimand->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $reprimand = Reprimand::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $reprimand->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $reprimand = Reprimand::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $reprimand->restore();

        return new DefaultResource($reprimand);
    }
}
