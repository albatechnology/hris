<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReimbursementCategory\AddUsersRequest;
use App\Http\Requests\Api\ReimbursementCategory\DeleteUsersRequest;
use App\Http\Requests\Api\ReimbursementCategory\EditUserRequest;
use App\Http\Requests\Api\ReimbursementCategory\GetUserReimbursementRequest;
use App\Http\Requests\Api\ReimbursementCategory\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\User\UserResource;
use App\Http\Services\Reimbursement\ReimbursementService;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class ReimbursementCategoryController extends BaseController
{
    public function __construct(private ReimbursementCategoryServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [
            AllowedInclude::callback('company', fn($q) => $q->select('id', 'name'))
        ];
    }

    public function index()
    {
        Gate::authorize('viewAny', ReimbursementCategory::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::exact('company_id'),
                'name',
                'limit_amount',
                'period_type',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'name',
                'limit_amount',
                'period_type',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', ReimbursementCategory::class);

        $data = $this->service->create($request->validated());

        return new DefaultResource($data);
    }

    public function update(int $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function getUserBalance(GetUserReimbursementRequest $request, int $userId)
    {
        if (auth()->id() == $userId) {
            $user = auth()->user();
        } else {
            $user = User::selectMinimalist()->findTenanted($userId);
        }

        $year = isset($request->filter['year']) ? $request->filter['year'] : date('Y');
        $month = isset($request->filter['month']) ? $request->filter['month'] : date('m');
        $date = date('Y-m-d', strtotime($year . '-' . $month . '-01'));
        $startDate = date($year . '-' . $month . '-01', strtotime($date));
        $endDate = date($year . '-' . $month . '-t', strtotime($date));
        $reimbursementCategoryId = $request->filter['reimbursement_category_id'] ?? null;

        $reimbursementCategories = $user->reimbursementCategories()->when($reimbursementCategoryId, fn($q) => $q->where('reimbursement_category_id', $reimbursementCategoryId))->get()
            ->map(function ($reimbursementCategory) use ($userId, $startDate, $endDate) {
                $totalReimbursementTaken = app(ReimbursementService::class)->getTotalReimbursementTaken($userId, $reimbursementCategory->id, $startDate, $endDate);

                $totalReimbursementBalance = $reimbursementCategory?->pivot->limit_amount - $totalReimbursementTaken;
                return [
                    'id' => $reimbursementCategory->id,
                    'name' => $reimbursementCategory->name,
                    'limit_amount' => $reimbursementCategory?->pivot->limit_amount,
                    'total_reimbursement_taken' => $totalReimbursementTaken,
                    'total_reimbursement_balance' => max($totalReimbursementBalance, 0),
                ];
            });

        return DefaultResource::collection($reimbursementCategories);
    }

    public function getUsers(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));

        $users = QueryBuilder::for(
            User::tenanted()->select('users.id', 'users.name', 'users.email', 'users.nik', 'user_reimbursement_categories.limit_amount')
                ->whereHas('reimbursementCategories', fn($q) => $q->where('reimbursement_categories.id', $id))
                ->join('user_reimbursement_categories', function (\Illuminate\Database\Query\JoinClause $join) use ($id) {
                    $join->on('users.id', '=', 'user_reimbursement_categories.user_id')
                        ->where('user_reimbursement_categories.reimbursement_category_id', $id);
                })
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
        $data = $this->service->findById($id, fn($q) => $q->select('id', 'limit_amount'));

        $datas = collect($request->users ?? [])->map(function ($item) {
            return [
                'user_id' => $item['id'],
                'limit_amount' => $item['limit_amount'],
            ];
        });

        $this->service->addUsers($data, $datas);

        return $this->createdResponse();
    }

    public function editUser(EditUserRequest $request, int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));

        $this->service->editUsers($data, $request->validated());

        return $this->updatedResponse();
    }

    public function deleteUsers(DeleteUsersRequest $request, int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));

        $this->service->deleteUsers($data, $request->user_ids ?? []);

        return $this->deletedResponse();
    }
}
