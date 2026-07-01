<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\Api\Reimbursement\StoreRequest;
use App\Http\Requests\Api\ReimbursementCategory\GetUserReimbursementRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Reimbursement\ReimbursementServiceInterface;
use App\Models\Reimbursement;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReimbursementController extends BaseController
{
    public function __construct(private ReimbursementServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [];
    }

    public function index(GetUserReimbursementRequest $request)
    {
        Gate::authorize('viewAny', Reimbursement::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->with([
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')),
                'user' => fn($q) => $q->select('id', 'name'),
                'reimbursementCategory' => fn($q) => $q->select('id', 'name'),
            ]),
            [
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('reimbursement_category_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                AllowedFilter::scope('year', 'whereYearIs'),
                AllowedFilter::scope('month', 'whereMonthIs'),
                'date',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'user_id',
                'reimbursement_category_id',
                'date',
                'amount',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $id): DefaultResource
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Reimbursement::class);

        $data = $this->service->create($request->validated());

        return new DefaultResource($data);
    }

    public function destroy(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function approve(NewApproveRequest $request, int $id)
    {
        $data = Reimbursement::findTenanted($id);
        $requestApproval = $data->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$data->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($data->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $requestApproval->update($request->validated());
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($data);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = Reimbursement::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])
            ->when($request->branch_id, fn($q) => $q->whereBranch($request->branch_id))
            ->when($request->name, fn($q) => $q->whereUserName($request->name))
            ->when($request->created_at, fn($q) => $q->createdAt($request->created_at))
            ->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = Reimbursement::myApprovals()
            ->with([
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')),
                'user' => fn($q) => $q->select('id', 'name'),
                'reimbursementCategory' => fn($q) => $q->select('id', 'name'),
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('reimbursement_category_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus'),
                AllowedFilter::scope('branch_id', 'whereBranch'),
                AllowedFilter::scope('name', 'whereUserName'),
                'date',
                'created_at',
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'reimbursement_category_id',
                'date',
                'amount',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
