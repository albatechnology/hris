<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Incident\StoreRequest;
use App\Http\Requests\Api\Incident\ExportRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Incident\IncidentServiceInterface;
use App\Models\Incident;
use App\Enums\MediaCollection;
use Exception;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class IncidentController extends BaseController
{
    public function __construct(private IncidentServiceInterface $service)
    {
        parent::__construct();
    }

    public function index()
    {
        Gate::authorize('viewAny', Incident::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->with([
                'user' => fn($q) => $q->selectMinimalist()->with(
                    'branch',
                    fn($q) => $q->selectMinimalist()
                ),
                'incidentType',
                'media'
            ]),
            [
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('incident_type_id'),
            ],
            allowedSorts: [
                'id',
                'company_id',
                'branch_id',
                'user_id',
                'incident_type_id',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(string $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        $data->load(['user' => fn($q) => $q->selectMinimalist()->with('branch', fn($q) => $q->selectMinimalist()), 'incidentType', 'media']);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Incident::class);

        try {
            $data = $this->service->create(array_merge($request->validated(), [
                'user_id' => auth('sanctum')->id(),
            ]));

            if ($request->hasFile('file')) {
                foreach ($request->file('file') as $file) {
                    if ($file->isValid()) {
                        $data->addMedia($file)->toMediaCollection(MediaCollection::DEFAULT->value);
                    }
                }
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function update(string $id, StoreRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function export123(ExportRequest $request)
    {
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
            'Content-Security-Policy' => "default-src 'self' data: https: 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
        ];
        return (new \App\Exports\Incident\ExportIncident($request))->download('incidents.xls', \Maatwebsite\Excel\Excel::XLS, $headers);
    }

    public function export(ExportRequest $request)
    {
        $companyId = $request->filter['company_id'] ?? null;
        $branchId = $request->filter['branch_id'] ?? null;
        $createdAtStartDate = $request->filter['created_at_start_date'] ?? null;
        $createdAtEndDate = $request->filter['created_at_end_date'] ?? null;
        $data = $request->filter['incident_type_id'] ?? null;

        $data =  Incident::tenanted()
            ->when($companyId, fn($q) => $q->whereHas('branch', fn($q) => $q->where('company_id', $companyId)))
            ->when($createdAtStartDate, fn($q) => $q->createdAtStart($createdAtStartDate))
            ->when($createdAtEndDate, fn($q) => $q->createdAtEnd($createdAtEndDate))
            ->when($data, fn($q) => $q->whereHas('incidentType', fn($q) => $q->where('id', $data)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with('user', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('incidentType', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('media')
            ->get();

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Cache-Control' => 'max-age=0',
            'Content-Security-Policy' => "default-src 'self' data: https: 'unsafe-inline'",
            'X-Content-Type-Options' => 'nosniff',
        ];

        $html = view('api.exports.incident.export', compact('incidents'))->render();

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="report-incidents-' . now()->format('Ymd') . '.xls"')
            ->header('Cache-Control', 'max-age=0');
    }
}
