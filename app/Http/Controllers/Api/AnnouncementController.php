<?php

namespace App\Http\Controllers\Api;

use App\Enums\JobLevel;
use App\Enums\NotificationType;
use App\Http\Requests\Api\Announcement\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Jobs\Announcement\NotifyAnnouncement;
use App\Mail\SetupPasswordMailer;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\Announcement\AnnouncementNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnnouncementController extends BaseController
{
  public function __construct()
  {
    parent::__construct();
    // $this->middleware('permission:announcement_access', ['only' => ['restore']]);
    // $this->middleware('permission:announcement_read', ['only' => ['index', 'show']]);
    // $this->middleware('permission:announcement_create', ['only' => 'store']);
    // $this->middleware('permission:announcement_edit', ['only' => 'update']);
    // $this->middleware('permission:announcement_delete', ['only' => ['destroy', 'forceDelete']]);
  }

  public function index()
  {
    $data = QueryBuilder::for(Announcement::tenanted()->with([
      'user' => function ($q) {
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
        AllowedFilter::exact('id'),
        AllowedFilter::exact('company_id'),
      ])->allowedIncludes(['user', 'branches', 'positions'])
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
      'user' => function ($q) {
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
      ->allowedIncludes(['user', 'branches', 'positions'])
      ->firstOrFail();

    return new DefaultResource($announcement);
  }

  public function store(StoreRequest $request)
  {
    DB::beginTransaction();
    try {
      $announcement = auth('sanctum')->user()->announcements()->create($request->validated());
      $users = User::tenanted();

      if ($request->branch_ids) {
        $announcement->branches()->attach(explode(',', $request->branch_ids));
        $users = $users->whereHas('branches', function ($q) use ($request) {
          $q->whereIn('branch_id', explode(',', $request->branch_ids));
        });
      }

      if ($request->position_ids) {
        $announcement->positions()->attach(explode(',', $request->position_ids));
        $users = $users->whereHas('positions', function ($q) use ($request) {
          $q->whereIn('position_id', explode(',', $request->position_ids));
        });
      }

      if ($request->job_levels) {
        collect(explode(',', $request->job_levels))->each(function($jobLevel) use($announcement) {
          $announcement->jobLevels()->create(['announcementable_type' => JobLevel::class, 'announcementable_id' => $jobLevel]);
        });
        $users = $users->whereHas('detail', function ($q) use ($request) {
          $q->whereIn('job_level', explode(',', $request->job_levels));
        });
      }

      $users = $users->get();

      NotifyAnnouncement::dispatch($announcement, $users);

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return $this->errorResponse($e->getMessage());
    }

    return new DefaultResource($announcement->load([
      'user' => function ($q) {
        $q->select('users.id', 'users.name');
      },
      'branches' => function ($q) {
        $q->select('branches.id', 'branches.name');
      },
      'positions' => function ($q) {
        $q->select('positions.id', 'positions.name');
      },
      'jobLevels',
    ]));
  }
}
