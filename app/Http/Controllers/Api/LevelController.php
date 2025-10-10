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

    /**
     * Display a listing of the resource.
     */
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorelRequest $request)
    {
        $level = $this->service->create($request->validated());

        return (new DefaultResource($level))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        try {
             $level = $this->service->findById($id);
            return new DefaultResource($level);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse("Data tidak ditemukan",[],404);
        }
       
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorelRequest $request, int $id)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);
        return $this->deletedResponse();
    }
}
