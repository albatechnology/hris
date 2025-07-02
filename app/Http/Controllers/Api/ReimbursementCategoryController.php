<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReimbursementCategory\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;
use App\Models\ReimbursementCategory;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class ReimbursementCategoryController extends BaseController
{
    public function __construct(protected ReimbursementCategoryServiceInterface $service)
    {
        parent::__construct();
        $this->middleware('permission:reimbursement_category_access', ['only' => ['restore']]);
        // $this->middleware('permission:reimbursement_category_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:reimbursement_category_create', ['only' => 'store']);
        $this->middleware('permission:reimbursement_category_edit', ['only' => 'update']);
        $this->middleware('permission:reimbursement_category_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(ReimbursementCategory::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'limit_amount',
                'period_type',
            ])
            ->allowedIncludes([
                AllowedInclude::callback('company', fn($q) => $q->select('id', 'name'))
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'limit_amount',
                'period_type',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $reimbursementCategory = $this->service->findById($id);
        return new DefaultResource($reimbursementCategory);
    }

    public function store(StoreRequest $request)
    {
        $reimbursementCategory = $this->service->create($request->validated());

        return new DefaultResource($reimbursementCategory);
    }

    public function update(int $id, StoreRequest $request)
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
