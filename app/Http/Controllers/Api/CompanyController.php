<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Company\StoreRequest;
use App\Http\Requests\Api\Company\UpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
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
                AllowedFilter::exact('group_id'),
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
            ])
            ->allowedSorts([
                'id',
                'group_id',
                'name',
                'country',
                'province',
                'city',
                'zip_code',
                'address',
                'created_at',
            ])
            ->paginate($this->per_page);

        return CompanyResource::collection($data);
    }

    public function show(int $id)
    {
        $company = Company::findTenanted($id);
        return new CompanyResource($company);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $company = Company::create($request->validated());
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();

            return $this->errorResponse($th->getMessage());
        }

        return new CompanyResource($company);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $company = Company::findTenanted($id);
        $company->update($request->validated());

        return (new CompanyResource($company))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $company = Company::findTenanted($id);
        $company->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $company = Company::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $company->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $company = Company::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $company->restore();

        return new CompanyResource($company);
    }
}
