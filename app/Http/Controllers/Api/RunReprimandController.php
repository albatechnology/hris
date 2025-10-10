<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\RunReprimand\StoreRequest;
use App\Http\Requests\Api\RunReprimand\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RunReprimand;
use App\Services\RunReprimandService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RunReprimandController extends BaseController
{
    public function __construct(public RunReprimandService $runReprimandService)
    {
        parent::__construct();
        $this->middleware('permission:run_reprimand_access', ['only' => ['restore']]);
        $this->middleware('permission:run_reprimand_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:run_reprimand_create', ['only' => 'store']);
        $this->middleware('permission:run_reprimand_edit', ['only' => 'update']);
        $this->middleware('permission:run_reprimand_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function allReprimand(int $id)
    {
        $runReprimand = RunReprimand::findTenanted($id);
        $data = $this->runReprimandService->allReprimand($runReprimand);
        return DefaultResource::collection($data);
    }

    public function index()
    {
        $data = QueryBuilder::for(RunReprimand::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'type',
            ])
            ->allowedIncludes([
                'company',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'type',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $runReprimand = RunReprimand::findTenanted($id);
        return new DefaultResource($runReprimand->loadMissing([
            'company',
        ]));
    }

    public function store(StoreRequest $request)
    {
        $res = $this->runReprimandService->store($request);

        return response()->json([
            'run' => $res['run'],
            'results' => $res['results'],
        ]);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $runReprimand = RunReprimand::findTenanted($id);

        DB::beginTransaction();
        try {
            $runReprimand->update($request->validated());
            $runReprimand->watchers()->sync($request->watcher_ids);

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::RunREPRIMAND->value;
                $runReprimand->clearMediaCollection($mediaCollection);
                $runReprimand->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($runReprimand))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $runReprimand = RunReprimand::findTenanted($id);
        $runReprimand->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $runReprimand = RunReprimand::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $runReprimand->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $runReprimand = RunReprimand::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $runReprimand->restore();

        return new DefaultResource($runReprimand);
    }
}
