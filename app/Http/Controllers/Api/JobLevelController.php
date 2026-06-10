<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\JobLevel\StoreRequest;
use App\Http\Requests\Api\JobLevel\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\JobLevelServiceInterface;
use App\Models\JobLevel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobLevelController extends BaseController
{
    public function __construct(protected JobLevelServiceInterface $service)
    {
        parent::__construct();
        // $this->middleware('permission:job_level_access', ['only' => ['restore']]);
        // $this->middleware('permission:job_level_read', ['only' => ['index', 'show']]);
        // $this->middleware('permission:job_level_create', ['only' => 'store']);
        // $this->middleware('permission:job_level_edit', ['only' => 'update']);
        // $this->middleware('permission:job_level_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $datas = QueryBuilder::for(JobLevel::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'code',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'code',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        $data = $this->service->create($request->validated());

        return new DefaultResource($data);
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

    public function forceDelete(int $id)
    {
        $this->service->forceDelete($id);

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $this->service->restore($id);

        return $this->okResponse();
    }
}
