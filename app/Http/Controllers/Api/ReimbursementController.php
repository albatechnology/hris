<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\NewApproveRequest;
use App\Http\Requests\Api\Reimbursement\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Reimbursement\ReimbursementServiceInterface;
use App\Models\Reimbursement;
use Exception;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReimbursementController extends BaseController
{
    public function __construct(private ReimbursementServiceInterface $service)
    {
        parent::__construct();
    }

    public function index(): ResourceCollection
    {
        $data = QueryBuilder::for(
            Reimbursement::tenanted()->with('approvals', fn($q) => $q->with('user', fn($q) => $q->select('id', 'name')))
                ->with([
                    'user' => fn($q) => $q->select('id', 'name'),
                    'shift' => fn($q) => $q->selectMinimalist(),
                ])
        )
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('shift_id'),
                'date',
                'is_after_shift'
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'shift_id',
                'date',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id): DefaultResource
    {
        $reimbursement = Reimbursement::findTenanted($id);
        $reimbursement->load(['user', 'reimbursementCategory']);
        return new DefaultResource($reimbursement);
    }

    public function store(StoreRequest $request)
    {
        $reimbursement = $this->service->create($request->validated());

        return new DefaultResource($reimbursement);
    }

    public function destroy(int $id)
    {
        $reimbursement = Reimbursement::findTenanted($id);
        // if (!$reimbursement->approval_status->is(ApprovalStatus::PENDING)) return $this->errorResponse(message: 'Cannot delete pending overtime request', code: 422);

        $reimbursement->delete();
        return $this->deletedResponse();
    }

    public function approve(NewApproveRequest $request, int $id)
    {
        $reimbursement = Reimbursement::findTenanted($id);
        $requestApproval = $reimbursement->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$reimbursement->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($reimbursement->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $requestApproval->update($request->validated());
        } catch (Exception $th) {
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($reimbursement);
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
                'user' => fn($q) => $q->select('id', 'name'),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
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
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
