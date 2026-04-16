<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Loan\StoreRequest;
use App\Http\Requests\Api\Loan\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\Loan\LoanResource;
use App\Interfaces\Services\Loan\LoanServiceInterface;
use App\Models\Loan;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;

class LoanController extends BaseController
{
    public function __construct(private LoanServiceInterface $service)
    {
        parent::__construct();
    }

    private function allowedIncludes(): array
    {
        return [
            AllowedInclude::callback('user', function ($query) {
                $query->select('id', 'name', 'nik', 'email');
            }),
        ];
    }

    public function index()
    {
        Gate::authorize('viewAny', Loan::class);

        $datas = $this->service->findAllPaginate(
            $this->per_page,
            null,
            [
                AllowedFilter::callback('name', function ($query, $value) {
                    $query->whereHas('user', fn($q) => $q->whereLike('name', $value));
                }),
                AllowedFilter::exact('user_id'),
                'code',
                'effective_date',
                'type',
                'interest',
            ],
            $this->allowedIncludes(),
            [
                'id',
                'user_id',
                'code',
                'effective_date',
                'type',
                'interest',
                'amount',
                'created_at',
            ],
            ['id', 'name'],
        )->through(function ($data) {
            $data->installment = $data->installment;
            $data->outstanding = $data->outstanding;
            $data->end_date = $data->end_date;
            $data->balance = $data->balance;
            return $data;
        });

        return DefaultResource::collection($datas);
    }

    public function show(int $id)
    {
        $data = $this->service->findById($id);
        Gate::authorize('view', $data);

        return new LoanResource($data->loadMissing([
            'details' => fn($q) => $q->with([
                'runPayrollUser' => fn($q) => $q->select('id', 'run_payroll_id')->with('runPayroll'),
                'userContact',
            ]),
            'user' => fn($q) => $q->select('id', 'name', 'nik', 'email'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        Gate::authorize('create', Loan::class);

        $data = $request->validated();
        $data['details'] = $request->details;

        $this->service->create($data);

        return $this->createdResponse();
    }

    public function update(int $id, UpdateRequest $request)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('update', $data);

        $data = $request->validated();
        $data['details'] = $request->details;

        $this->service->update($id, $data);

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->select('id'));
        Gate::authorize('delete', $data);

        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('forceDelete', $data);

        $this->service->forceDelete($id);

        return $this->forceDeletedResponse();
    }

    public function restore(int $id)
    {
        $data = $this->service->findById($id, fn($q) => $q->withTrashed()->select('id'));
        Gate::authorize('restore', $data);

        $this->service->restore($id);

        return $this->restoredResponse();
    }
}
