<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\Announcement\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\Announcement\AnnouncementServiceInterface;
use App\Jobs\Announcement\BulkNotifyAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;

class AnnouncementController extends BaseController
{
    public function __construct(protected AnnouncementServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return ['media'];
    }

    public function index()
    {
        Gate::authorize('viewAny', Announcement::class);

        $data = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->tenanted()->with([
                'createdBy' => function ($q) {
                    $q->select('users.id', 'users.name');
                },
                'branches' => function ($q) {
                    $q->select('branches.id', 'branches.name');
                },
                'positions' => function ($q) {
                    $q->select('positions.id', 'positions.name');
                },
                'jobLevels',
            ]),
            [
                AllowedFilter::exact('company_id'),
            ],
            $this->allowedIncludes(),
            [
                'id',
                'company_id',
                'created_at',
            ],
            [
                'id',
                'company_id',
                'created_at',
            ],
        );

        return DefaultResource::collection($data);
    }

    public function show(string $id)
    {
        $data = $this->service->findByIdOrFail($id);

        Gate::authorize('view', $data);

        $data->load([
            'createdBy' => function ($q) {
                $q->select('users.id', 'users.name');
            },
            'branches' => function ($q) {
                $q->select('branches.id', 'branches.name');
            },
            // 'positions' => function ($q) {
            //     $q->select('positions.id', 'positions.name');
            // },
            // 'jobLevels',
            'media',
        ]);

        return new DefaultResource($data);
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Announcement::class);

        DB::beginTransaction();
        try {
            $data = Announcement::create($request->validated());
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::ANNOUNCEMENT->value;
                $data->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            $query = User::select('id', 'fcm_token')->whereNotNull('fcm_token')->tenanted();

            if ($request->branch_ids) {
                $data->branches()->attach(explode(',', $request->branch_ids));
                $query->whereHas('branches', function ($q) use ($request) {
                    $q->whereIn('branch_id', explode(',', $request->branch_ids));
                });
            }

            // if ($request->position_ids) {
            //     $announcement->positions()->attach(explode(',', $request->position_ids));
            //     $query->whereIn('position_id', explode(',', $request->position_ids));
            //     // $query->whereHas('positions', function ($q) use ($request) {
            //     //     $q->whereIn('position_id', explode(',', $request->position_ids));
            //     // });
            // }

            // if ($request->department_ids) {
            //     $announcement->positions()->attach(explode(',', $request->department_ids));
            //     $query->whereIn('department_id', explode(',', $request->department_ids));
            //     // $query->whereHas('positions', function ($q) use ($request) {
            //     //     $q->whereIn('department_id', explode(',', $request->department_ids));
            //     // });
            // }

            //   if ($request->job_levels) {
            //     collect(explode(',', $request->job_levels))->each(function($jobLevel) use($data) {
            //       $data->jobLevels()->create(['announcementable_type' => JobLevel::class, 'announcementable_id' => $jobLevel]);
            //     });
            //     $query->whereHas('detail', function ($q) use ($request) {
            //       $q->whereIn('job_level', explode(',', $request->job_levels));
            //     });
            //   }

            $users = $query->get();

            if ($users->count()) {
                BulkNotifyAnnouncement::dispatch($data, $users);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function destroy(string $id)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(string $id)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(string $id)
    {
        $data = $this->service->findByIdOrFail($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }

    public function export(\App\Http\Requests\Api\Announcement\ExportRequest $request)
    {
        return (new \App\Exports\Announcement\ExportAnnouncement($request))->download('announcements.xlsx');
    }
}
