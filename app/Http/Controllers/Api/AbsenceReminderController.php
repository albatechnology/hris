<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\AbsenceReminder\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Jobs\AbsenceReminder\AbsenceReminderBatch;
use App\Mail\TestEmail;
use App\Models\AbsenceReminder;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
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
        AbsenceReminderBatch::dispatch();
        $user = User::where('id', 20)->select('id', 'name', 'email')->first();
        Mail::to($user)->send(new TestEmail());
        $data = QueryBuilder::for(AbsenceReminder::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                AllowedFilter::exact('client_id'),
            ])
            ->allowedIncludes(['company', 'client'])
            ->allowedSorts([
                'id',
                'company_id',
                'client_id',
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
            'client',
        ]));
    }

    public function update(int $id, UpdateRequest $request)
    {
        $absenceReminder = AbsenceReminder::findTenanted($id);

        $absenceReminder->update($request->validated());

        return (new DefaultResource($absenceReminder))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
