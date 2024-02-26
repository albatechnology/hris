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
                AllowedFilter::exact('id'),
                AllowedFilter::exact('company_id'),
            ])
            ->allowedIncludes(['company'])
            ->allowedSorts([
                'id', 'company_id', 'key', 'type', 'options', 'created_at',
            ])
            ->paginate($this->per_page);

        return CustomFieldResource::collection($data);
    }

    public function show(CustomField $customField)
    {
        return new CustomFieldResource($customField);
    }

    public function store(StoreRequest $request)
    {
        $customField = CustomField::create($request->validated());

        return new CustomFieldResource($customField);
    }

    public function update(CustomField $customField, StoreRequest $request)
    {
        $customField->update($request->validated());

        return (new CustomFieldResource($customField))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(CustomField $customField)
    {
        $customField->delete();

        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $customField = CustomField::withTrashed()->findOrFail($id);
        $customField->forceDelete();

        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $customField = CustomField::withTrashed()->findOrFail($id);
        $customField->restore();

        return new CustomFieldResource($customField);
    }
}
