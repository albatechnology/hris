<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReimbursementCategory\AddUsersRequest;
use App\Http\Requests\Api\ReimbursementCategory\EditUserRequest;
use App\Http\Requests\Api\ReimbursementCategory\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\User\UserResource;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;
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

    public function getUsers(int $id)
    {
        $reimbursementCategory = $this->service->findById($id, fn($q) => $q->select('id'));

        $users = QueryBuilder::for(
            User::tenanted()->select('id', 'name', 'email', 'nik')->whereHas('reimbursementCategories', fn($q) => $q->where('reimbursement_categories.id', $id))
        )
            ->allowedFilters([
                AllowedFilter::exact('branch_id'),
                AllowedFilter::callback('branch_id', function ($query, $value) {
                    if (!empty($value) || $value > 0) {
                        $query->where('branch_id', $value);
                    }
                }),
                AllowedFilter::scope('name', 'whereName'),
                'email',
                'type',
                'nik',
                'phone',
            ])
            ->allowedSorts([
                'id',
                'name',
            ])
            ->paginate($this->per_page);

        return UserResource::collection($users);
    }

    public function addUsers(AddUsersRequest $request, int $id)
    {
        $reimbursementCategory = $this->service->findById($id, fn($q) => $q->select('id', 'limit_amount'));

        $this->service->addUsers($reimbursementCategory, $request->user_ids ?? []);

        return $this->createdResponse();
    }

    public function editUser(EditUserRequest $request, int $id)
    {
        $reimbursementCategory = $this->service->findById($id, fn($q) => $q->select('id'));

        $this->service->editUsers($reimbursementCategory, $request->validated());

        return $this->updatedResponse();
    }

    public function deleteUsers(AddUsersRequest $request, int $id)
    {
        $reimbursementCategory = $this->service->findById($id, fn($q) => $q->select('id'));

        $this->service->deleteUsers($reimbursementCategory, $request->user_ids ?? []);

        return $this->deletedResponse();
    }
}
