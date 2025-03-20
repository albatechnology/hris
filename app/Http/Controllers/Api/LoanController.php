<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Loan\StoreRequest;
use App\Http\Requests\Api\Loan\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Http\Resources\Loan\LoanResource;
use App\Models\Loan;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class LoanController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:loan_access', ['only' => ['restore']]);
        $this->middleware('permission:loan_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:loan_create', ['only' => 'store']);
        $this->middleware('permission:loan_edit', ['only' => 'update']);
        $this->middleware('permission:loan_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Loan::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                'code',
                'effective_date',
                'type',
                'installment',
                'interest',
            ])
            ->allowedIncludes([
                AllowedInclude::callback('user', function ($query) {
                    $query->select('id', 'name', 'last_name', 'nik', 'email');
                }),
            ])
            ->allowedSorts([
                'id',
                'user_id',
                'code',
                'effective_date',
                'type',
                'installment',
                'interest',
                'amount',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $loan = Loan::findTenanted($id);
        return new LoanResource($loan->loadMissing([
            'details' => fn($q) => $q->with([
                'runPayrollUser' => fn($q) => $q->select('id', 'run_payroll_id')->with('runPayroll'),
                'userContact'
            ]),
            'user' => fn($q) => $q->select('id', 'name', 'last_name', 'nik', 'email'),
        ]));
    }

    public function store(StoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $loan = Loan::create($request->validated());
            $loan->details()->createMany($request->details);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage());
        }

        return new DefaultResource($loan);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $loan = Loan::findTenanted($id);
        // $loan->update($request->validated());

        return (new DefaultResource($loan))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $loan = Loan::findTenanted($id);
        $loan->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $loan = Loan::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $loan->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $loan = Loan::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $loan->restore();

        return new DefaultResource($loan);
    }
}
