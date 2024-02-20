<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserCustomField\StoreRequest;
use App\Http\Resources\UserCustomField\UserCustomFieldResource;
use App\Models\UserCustomField;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserCustomFieldController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        //  $this->middleware('permission:user_cumtom_field_access', ['only' => ['restore']]);
        //  $this->middleware('permission:user_cumtom_field_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:user_cumtom_field_create', ['only' => 'store']);
        $this->middleware('permission:user_cumtom_field_edit', ['only' => 'update']);
        //  $this->middleware('permission:user_cumtom_field_delete', ['only' => ['destroy', 'forceDelete']]);
    }

    public function index()
    {
        $data = QueryBuilder::for(UserCustomField::tenanted())
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts([
                'id', 'custom_field_id', 'user_id', 'value', 'created_at'
            ])
            ->paginate($this->per_page);

        return UserCustomFieldResource::collection($data);
    }

    public function show(UserCustomField $userCustomField)
    {
        return new UserCustomFieldResource($userCustomField);
    }

    public function store(StoreRequest $request)
    {
        $userCustomField = UserCustomField::create($request->validated());

        return new UserCustomFieldResource($userCustomField);
    }

    public function update(UserCustomField $userCustomField, StoreRequest $request)
    {
        $userCustomField->update($request->validated());

        return (new UserCustomFieldResource($userCustomField))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(UserCustomField $userCustomField)
    {
        $userCustomField->delete();
        return $this->deletedResponse();
    }

    public function forceDelete($id)
    {
        $userCustomField = UserCustomField::withTrashed()->findOrFail($id);
        $userCustomField->forceDelete();
        return $this->deletedResponse();
    }

    public function restore($id)
    {
        $userCustomField = UserCustomField::withTrashed()->findOrFail($id);
        $userCustomField->restore();
        return new UserCustomFieldResource($userCustomField);
    }
}
