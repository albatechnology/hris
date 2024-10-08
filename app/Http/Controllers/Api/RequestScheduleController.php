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
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RequestScheduleController extends BaseController
{
  public function __construct()
  {
    parent::__construct();
    $this->middleware('permission:user_access', ['only' => ['restore']]);
    $this->middleware('permission:user_read', ['only' => ['index', 'show']]);
    $this->middleware('permission:user_create', ['only' => 'store']);
    $this->middleware('permission:user_edit', ['only' => 'update']);
    $this->middleware('permission:user_delete', ['only' => ['destroy', 'forceDelete']]);
    $this->middleware('permission:user_edit', ['only' => 'update']);
    $this->middleware('permission:request_change_data_create', ['only' => 'approve']);
  }

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
    if (!$requestSchedule->approval_status->is(ApprovalStatus::PENDING)) {
      return $this->errorResponse(message: 'Status can not be changed', code: \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    DB::beginTransaction();
    try {
      $requestSchedule->update($approveRequest->validated());
      if ($requestSchedule->approval_status->is(ApprovalStatus::APPROVED)) {
        $schedule = Schedule::where([
          ['company_id', '=', $requestSchedule->company_id],
          ['type', '=', $requestSchedule->type->value],
          // ['name, '=',> $requestSchedule->name],
          ['effective_date', '=', $requestSchedule->effective_date],
          ['is_overide_national_holiday', '=', $requestSchedule->is_overide_national_holiday],
          ['is_overide_company_holiday', '=', $requestSchedule->is_overide_company_holiday],
          ['is_include_late_in', '=', $requestSchedule->is_include_late_in],
          ['is_include_early_out', '=', $requestSchedule->is_include_early_out],
          ['is_flexible', '=', $requestSchedule->is_flexible],
          ['is_generate_timeoff', '=', $requestSchedule->is_generate_timeoff],
        ])->first();

        if (!$schedule) {
          $schedule = Schedule::create([
            'company_id' => $requestSchedule->company_id,
            'type' => $requestSchedule->type,
            'name' => $requestSchedule->name,
            'effective_date' => $requestSchedule->effective_date,
            'is_overide_national_holiday' => $requestSchedule->is_overide_national_holiday,
            'is_overide_company_holiday' => $requestSchedule->is_overide_company_holiday,
            'is_include_late_in' => $requestSchedule->is_include_late_in,
            'is_include_early_out' => $requestSchedule->is_include_early_out,
            'is_flexible' => $requestSchedule->is_flexible,
            'is_generate_timeoff' => $requestSchedule->is_generate_timeoff,
          ]);

          $data = [];
          foreach ($request->shifts ?? [] as $shift) {
            $data[$shift['id']] = ['order' => $shift['order']];
          }
          $schedule->shifts()->sync($data);
        }

        $schedule->users()->syncWithoutDetaching([$requestSchedule->user_id]);
      }

      DB::commit();
    } catch (\Exception $th) {
      DB::rollBack();
      return $this->errorResponse($th->getMessage());
    }

    return $this->updatedResponse();
  }
}
