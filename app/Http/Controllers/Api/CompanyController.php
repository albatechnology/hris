<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Company\StoreRequest;
use App\Http\Requests\Api\Company\UpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use App\Services\TimeoffRegulationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:company_access', ['only' => ['index', 'show', 'restore']]);
        $this->middleware('permission:company_access', ['only' => ['restore']]);
        $this->middleware('permission:company_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:company_create', ['only' => 'store']);
        $this->middleware('permission:company_edit', ['only' => 'update']);
        $this->middleware('permission:company_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Company::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('group_id'),
                'name', 'country', 'province', 'city', 'zip_code', 'address',
            ])
            ->allowedSorts([
                'id', 'group_id', 'name', 'country', 'province', 'city', 'zip_code', 'address', 'created_at',
            ])
            ->paginate($this->per_page);

        return CompanyResource::collection($data);
    }

    public function show(Company $company)
    {
        return new CompanyResource($company);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $company = Company::create($request->validated());
            TimeoffRegulationService::create($company, $request->renew_type);
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new CompanyResource($company);
    }

    public function update(Company $company, UpdateRequest $request)
    {
        $company->update($request->validated());

        return (new CompanyResource($company))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->restore();

        return new CompanyResource($company);
    }
}
