<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Bank\StoreRequest;
use App\Http\Requests\Api\Bank\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\BankServiceInterface;
use App\Models\Bank;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BankController extends BaseController
{
    public function __construct(protected BankServiceInterface $service)
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
        $bank = $this->service->findById($id);
        return new DefaultResource($bank);
    }

    public function store(StoreRequest $request)
    {
        $bank = $this->service->create($request->validated());

        return new DefaultResource($bank);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $this->service->findById($id);
        $this->service->update($id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $id)
    {
        $this->service->findById($id);
        $this->service->delete($id);

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $this->service->forceDelete($id);

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $this->service->restore($id);

        return $this->okResponse();
    }
}
