<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Level\StorelRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Level\LevelServiceInterface;
use App\Models\Level;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LevelController extends BaseController
{
    public function __construct(protected LevelServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        $data = QueryBuilder::for(Level::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function store(StorelRequest $request)
    {
        $level = $this->service->create($request->validated());

        return $this->createdResponse();
    }

    public function show(int $id)
    {
        try {
            $level = $this->service->findById($id);
            return new DefaultResource($level);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Data tidak ditemukan", [], 404);
        }
    }

    public function update(StorelRequest $request, int $id)
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
