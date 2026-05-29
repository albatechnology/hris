<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Company\StoreRequest;
use App\Http\Requests\Api\Company\UpdateRequest;
use App\Http\Resources\Company\CompanyResource;
use App\Interfaces\Services\Company\CompanyServiceInterface;
use App\Models\Company;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends BaseController
{
    public function __construct(private CompanyServiceInterface $service)
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
        $company = $this->service->create($request->validated());
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

    public function organizationChart(int $id)
    {
        $companies = Company::tenanted()
            ->where('id', $id)
            ->with([
                'divisions' => fn($query) => $query->orderBy('order'),
                'divisions.user:id,name,email',
                'divisions.departments' => fn($query) => $query->orderBy('order'),
                'divisions.departments.user:id,name,email',
                'divisions.departments.positions' => fn($query) => $query->orderBy('order'),
                'divisions.departments.positions.users:id,name,email,position_id,department_id',
            ])
            ->get();

        $payload = $companies->map(fn($company) => [
            'id' => $company->id,
            'name' => $company->name,
            'divisions' => $company->divisions->map(fn($division) => [
                'id' => $division->id,
                'name' => $division->name,
                'order' => $division->order,
                'leader' => $division->user ? [
                    'id' => $division->user->id,
                    'name' => $division->user->name,
                    'email' => $division->user->email,
                ] : null,
                'departments' => $division->departments->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'order' => $department->order,
                    'leader' => $department->user ? [
                        'id' => $department->user->id,
                        'name' => $department->user->name,
                        'email' => $department->user->email,
                    ] : null,
                    'positions' => $department->positions->map(fn($position) => [
                        'id' => $position->id,
                        'name' => $position->name,
                        'order' => $position->order,
                        'users' => $position->users->map(fn($user) => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ]),
                    ]),
                ]),
            ]),
        ]);

        return response()->json(['data' => $payload]);
    }
}
