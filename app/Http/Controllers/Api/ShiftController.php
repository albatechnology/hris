<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Shift\StoreRequest;
use App\Http\Requests\Api\Attendance\ExportReportRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Shift\ShiftServiceInterface;
use App\Models\Shift;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;

class ShiftController extends BaseController
{
    public function __construct(private ShiftServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [
            'company',
            AllowedInclude::callback('schedules', function ($query) {
                $query->select('id', 'name');
            }),
        ];
    }

    public function index()
    {
        Gate::authorize('viewAny', Shift::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->where(fn($q) => $q->orWhereNull('company_id')),
            [
                AllowedFilter::exact('company_id'),
                'name',
                'type',
                'clock_in',
                'clock_out',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'name',
                'clock_in',
                'clock_out',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $shift = $this->service->findById($id);
        Gate::authorize('view', $shift);

        $shift->load(['schedules' => fn($q) => $q->select('id', 'name')]);
        return new DefaultResource($shift);
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_job_position_ids'] = $request->job_position_ids;
        $data['show_in_request_job_level_ids'] = $request->level_ids;
        $shift = Shift::create($data);
        Gate::authorize('create', Shift::class);

        $this->service->create($data);

        return $this->createdResponse();
    }

    public function update(string $id, StoreRequest $request)
    {
        $shift = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $shift);

        $data = $request->validated();
        $data['show_in_request_branch_ids'] = $request->branch_ids;
        $data['show_in_request_job_position_ids'] = $request->job_position_ids;
        $data['show_in_request_level_ids'] = $request->level_ids;
        $shift->update($data);
        $this->service->update($id, $data);

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $shift = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $shift);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $shift = $this->service->findById($id, fn($q) => $q->withTrashed());
        Gate::authorize('forceDelete', $shift);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $shift = $this->service->findById($id, fn($q) => $q->withTrashed());
        Gate::authorize('restore', $shift);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function reportShiftUsers(ExportReportRequest $request, ?string $export = null)
    {
        $result = $this->service->reportShiftUsers($request->filter, $export);

        if ($export) {
            return $result;
        }

        return DefaultResource::collection($result);
    }

    public function importShiftUsers(\Illuminate\Http\Request $request)
    {
        Gate::authorize('viewAny', Shift::class);

        $request->validate([
            'file' => 'required|file|mimes:xlsx',
        ]);

        $this->service->importShiftUsers($request->file('file'));

        return $this->createdResponse("File imported successfully");
    }
}
