<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaCollection;
use App\Http\Requests\Api\Announcement\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Jobs\Announcement\BulkNotifyAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:announcement_access', ['only' => ['restore']]);
        // $this->middleware('permission:announcement_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:announcement_create', ['only' => 'store']);
        // $this->middleware('permission:announcement_edit', ['only' => 'update']);
        $this->middleware('permission:announcement_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Announcement::tenanted()->with([
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
        ]))
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['media'])
            ->allowedSorts([
                'id',
                'company_id'
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $announcement = QueryBuilder::for(Announcement::tenanted()->where('id', $id)->with([
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
            'media',
        ]))->firstOrFail();

        return new DefaultResource($announcement);
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $announcement = Announcement::create($request->validated());
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $mediaCollection = MediaCollection::REPRIMAND->value;
                $announcement->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            $query = User::select('id', 'fcm_token')->whereNotNull('fcm_token')->tenanted();

            if ($request->branch_ids) {
                $announcement->branches()->attach(explode(',', $request->branch_ids));
                $query->whereHas('branches', function ($q) use ($request) {
                    $q->whereIn('branch_id', explode(',', $request->branch_ids));
                });
            }

            if ($request->position_ids) {
                $announcement->positions()->attach(explode(',', $request->position_ids));
                $query->whereHas('positions', function ($q) use ($request) {
                    $q->whereIn('position_id', explode(',', $request->position_ids));
                });
            }

            if ($request->department_ids) {
                $announcement->positions()->attach(explode(',', $request->department_ids));
                $query->whereHas('positions', function ($q) use ($request) {
                    $q->whereIn('department_id', explode(',', $request->department_ids));
                });
            }

            //   if ($request->job_levels) {
            //     collect(explode(',', $request->job_levels))->each(function($jobLevel) use($announcement) {
            //       $announcement->jobLevels()->create(['announcementable_type' => JobLevel::class, 'announcementable_id' => $jobLevel]);
            //     });
            //     $query->whereHas('detail', function ($q) use ($request) {
            //       $q->whereIn('job_level', explode(',', $request->job_levels));
            //     });
            //   }

            $users = $query->get();

            if ($users->count()) {
                BulkNotifyAnnouncement::dispatch($announcement, $users);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($announcement->load([
            'branches' => function ($q) {
                $q->select('branches.id', 'branches.name');
            },
            'positions' => function ($q) {
                $q->select('positions.id', 'positions.name');
            },
            'jobLevels',
        ]));
    }

    public function destroy(int $id)
    {
        $announcement = Announcement::findTenanted($id);
        $announcement->delete();

        return $this->deletedResponse();
    }
}
