<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Bank\StoreRequest;
use App\Http\Requests\Api\Bank\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Bank;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:bank_access', ['only' => ['restore']]);
        $this->middleware('permission:bank_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:bank_create', ['only' => 'store']);
        $this->middleware('permission:bank_edit', ['only' => 'update']);
        $this->middleware('permission:bank_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(Bank::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
            ])
            ->allowedSorts([
                'id',
                'company_id',
                'name',
                'account_no',
                'account_holder',
                'code',
                'branch',
                'created_at',
            ])
            ->paginate($this->per_page);

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $bank = Bank::findTenanted($id);
        return new DefaultResource($bank);
    }

    public function store(StoreRequest $request)
    {
        $bank = Bank::create($request->validated());

        return new DefaultResource($bank);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $bank = Bank::findTenanted($id);
        $bank->update($request->validated());

        return (new DefaultResource($bank))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $bank = Bank::findTenanted($id);
        $bank->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $bank = Bank::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $bank->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $bank = Bank::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $bank->restore();

        return new DefaultResource($bank);
    }
}
