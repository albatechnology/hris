<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApprovalStatus;
use App\Enums\MediaCollection;
use App\Http\Requests\Api\ApproveRequest;
use App\Http\Requests\Api\UserTransfer\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\UserTransfer;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class UserTransferController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:user_transfer_access', ['only' => ['restore']]);
        $this->middleware('permission:user_transfer_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_transfer_create', ['only' => 'store']);
        $this->middleware('permission:user_transfer_edit', ['only' => 'update']);
        $this->middleware('permission:user_transfer_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    private function getAllowedIncludes()
    {
        return [
            AllowedInclude::callback('user', function ($q) {
                $q->select('id', 'name', 'nik');
            }),
            AllowedInclude::callback('approval', function ($q) {
                $q->select('id', 'name');
            }),
            // AllowedInclude::callback('manager', function ($q) {
            //     $q->select('id', 'name');
            // }),
        ];
    }

    public function index()
    {
        $data = QueryBuilder::for(UserTransfer::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('approval_id'),
                AllowedFilter::exact('parent_id'),
                'type',
                'employment_status',
                'approval_status'
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'user_id',
                'effective_date',
                'created_at'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $userTransfer = UserTransfer::with([
            'user' => fn($q) => $q->select('id', 'name', 'nik'),
            // 'approval' => fn($q) => $q->select('id', 'name'),
            // 'manager' => fn ($q) => $q->select('id', 'name'),
        ])->findTenanted($id);

        return new DefaultResource($userTransfer);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $userTransfer = UserTransfer::create($request->validated());
            // $userTransfer->branches()->createMany(
            //     collect($request->branch_ids)->unique()->values()
            //         ->map(function ($branchId) {
            //             return ['branch_id' => $branchId];
            //         })->all()
            // );

            // $userTransfer->positions()->createMany($request->positions ?? []);

            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::USER_TRANSFER->value;
                $userTransfer->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            DB::commit();

            // if ($userTransfer->is_notify_manager) {
            // }
            // if ($userTransfer->is_notify_user) {
            // }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($userTransfer);
    }

    public function update(UserTransfer $userTransfer, StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $userTransfer->update($request->validated());

            DB::commit();

            if ($userTransfer->is_notify_manager) {
            }
            if ($userTransfer->is_notify_user) {
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return (new DefaultResource($userTransfer))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(UserTransfer $userTransfer)
    {
        $userTransfer->delete();

        return $this->deletedResponse();
    }

    public function approve(ApproveRequest $request, UserTransfer $userTransfer)
    {
        if ($userTransfer->approval_status->is(ApprovalStatus::APPROVED) && ($request->approval_status == ApprovalStatus::REJECTED->value)) {
            return $this->errorResponse('User transfer already approved', code: Response::HTTP_CONFLICT);
        }

        DB::beginTransaction();
        try {
            $userTransfer->update($request->validated());

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            return $this->errorResponse($th->getMessage());
        }

        return new DefaultResource($userTransfer);
    }

    public function countTotalApprovals(\App\Http\Requests\ApprovalStatusRequest $request)
    {
        $total = DB::table('user_transfers')->where('approved_by', auth('sanctum')->id())->where('approval_status', $request->filter['approval_status'])->count();

        return response()->json(['message' => $total]);
    }

    public function approvals()
    {
        $query = UserTransfer::tenanted()->whereHas('user', fn($q) => $q->where('approval_id', auth('sanctum')->id()));
        // ->with([
        //     'user' => fn ($q) => $q->select('id', 'name', 'nik'),
        //     // 'approvedBy' => fn ($q) => $q->select('id', 'name'),
        //     // 'shift' => fn ($q) => $q->selectMinimalist(),
        // ]);

        $data = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('shift_id'),
                'approval_status',
                'date',
                'is_after_shift'
            ])
            ->allowedIncludes($this->getAllowedIncludes())
            ->allowedSorts([
                'id',
                'user_id',
                'effective_date',
                'created_at'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }
}
