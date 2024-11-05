<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Http\Requests\Api\RequestSchedule\ApproveRequest;
use App\Http\Requests\Api\RequestSchedule\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestSchedule;
use App\Models\Schedule;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RequestScheduleController extends BaseController
{
    // public function __construct()
    // {
    //     parent::__construct();
    //     $this->middleware('permission:user_access', ['only' => ['restore']]);
    //     $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
    //     $this->middleware('permission:user_create', ['only' => 'store']);
    //     $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);
    //     $this->middleware('permission:user_edit', ['only' => 'update']);
    //     $this->middleware('permission:request_change_data_create', ['only' => 'approve']);
    // }

    public function index()
    {
        $data = QueryBuilder::for(RequestSchedule::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
                'name',
                'type',
                'effective_date',
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'effective_date',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(RequestSchedule $requestSchedule)
    {
        return new DefaultResource($requestSchedule->load(['shifts' => fn($q) => $q->orderBy('order')]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $requestSchedule = auth('sanctum')->user()->requestSchedules()->create($request->validated());

            $data = [];
            foreach ($request->shifts ?? [] as $shift) {
                $data[$shift['id']] = ['order' => $shift['order']];
            }
            $requestSchedule->shifts()->sync($data);

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($requestSchedule->refresh()->load(['shifts' => fn($q) => $q->orderBy('order')]));
    }

    public function update(RequestSchedule $requestSchedule, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $requestSchedule->update($request->validated());

            $data = [];
            foreach ($request->shifts ?? [] as $shift) {
                $data[$shift['id']] = ['order' => $shift['order']];
            }
            $requestSchedule->shifts()->sync($data);

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($requestSchedule->refresh()->load(['shifts' => fn($q) => $q->orderBy('order')]));
    }

    public function destroy(Schedule $requestSchedule)
    {
        $requestSchedule->delete();

        return $this->deletedResponse();
    }

    public function approve(ApproveRequest $approveRequest, RequestSchedule $requestSchedule): DefaultResource|JsonResponse
    {
        // if (!$requestSchedule->approval_status->is(ApprovalStatus::PENDING)) {
        //     return $this->errorResponse(message: 'Status can not be changed', code: \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
        // }

        $requestApproval = $requestSchedule->approvals()->where('user_id', auth()->id())->first();

        if (!$requestApproval) return $this->errorResponse(message: 'You are not registered as approved', code: Response::HTTP_NOT_FOUND);

        if (!$requestSchedule->isDescendantApproved()) return $this->errorResponse(message: 'You have to wait for your subordinates to approve', code: Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($requestSchedule->approval_status == ApprovalStatus::APPROVED->value || $requestApproval->approval_status->in([ApprovalStatus::APPROVED, ApprovalStatus::REJECTED])) {
            return $this->errorResponse(message: 'Status can not be changed', code: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $requestApproval->update($approveRequest->validated());
            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return $this->updatedResponse();
    }

    public function countTotalApprovals(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'filter.approval_status' => ['required', \Illuminate\Validation\Rule::in([...ApprovalStatus::cases(), 'on_progress'])],
        ]);

        // $total = DB::table('request_change_data')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();
        $total = RequestSchedule::myApprovals()
            ->whereApprovalStatus($request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = RequestSchedule::myApprovals()
            ->with([
                'shifts' => fn($q) => $q->select('id', 'name', 'type', 'clock_in', 'clock_out')->orderBy('order'),
                'user' => fn($q) => $q->select('id', 'name'),
                'approvals' => fn($q) => $q->with('user', fn($q) => $q->select('id', 'name'))
            ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::scope('approval_status', 'whereApprovalStatus')
            ])
            ->allowedIncludes('details')
            ->allowedSorts([
                'id',
                'user_id',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
