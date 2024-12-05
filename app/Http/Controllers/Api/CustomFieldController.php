<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CustomField\StoreRequest;
use App\Http\Resources\CustomField\CustomFieldResource;
use App\Models\CustomField;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomFieldController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:custom_field_access', ['only' => ['restore']]);
        $this->middleware('permission:custom_field_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:custom_field_create', ['only' => 'store']);
        $this->middleware('permission:custom_field_edit', ['only' => 'update']);
        $this->middleware('permission:custom_field_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(CustomField::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id',
                'company_id',
                'key',
                'type',
                'options',
                'created_at',
            ])
            ->paginate($this->per_page);

        return CustomFieldResource::collection($data);
    }

    public function show(int $id)
    {
        $customField = CustomField::findTenanted($id);
        return new CustomFieldResource($customField);
    }

    public function store(StoreRequest $request)
    {
        $customField = CustomField::create($request->validated());

        return new CustomFieldResource($customField);
    }

    public function update(int $id, StoreRequest $request)
    {
        $customField = CustomField::findTenanted($id);
        $customField->update($request->validated());

        return (new CustomFieldResource($customField))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(int $id)
    {
        $customField = CustomField::findTenanted($id);
        $customField->delete();

        return $this->deletedResponse();
    }

    public function forceDelete(int $id)
    {
        $customField = CustomField::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $customField->forceDelete();

        return $this->deletedResponse();
    }

    public function restore(int $id)
    {
        $customField = CustomField::withTrashed()->tenanted()->where('id', $id)->firstOrFail();
        $customField->restore();

        return new CustomFieldResource($customField);
    }
}
