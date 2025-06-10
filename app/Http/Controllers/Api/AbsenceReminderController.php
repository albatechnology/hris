<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AbsenceReminder\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\AbsenceReminder;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AbsenceReminderController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:absence_reminder_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:absence_reminder_edit', ['only' => 'update']);
    }

    public function index()
    {
        $data = QueryBuilder::for(AbsenceReminder::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('branch_id'),
                'is_active'
            ])
            ->allowedIncludes([
                'company',
                'branch'
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'branch_id',
                'is_active',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $absenceReminder = AbsenceReminder::findTenanted($id);
        return new DefaultResource($absenceReminder->loadMissing([
            'company',
            'branch',
        ]));
    }

    public function update(int $id, UpdateRequest $request)
    {
        $absenceReminder = AbsenceReminder::findTenanted($id);

        $absenceReminder->update($request->validated());

        return (new DefaultResource($absenceReminder))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
