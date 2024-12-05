<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Setting\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Models\Setting;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SettingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('permission:setting_access', ['only' => ['restore']]);
        $this->middleware('permission:setting_read', ['only' => ['index', 'show']]);
        $this->middleware('permission:setting_edit', ['only' => 'update']);
    }

    public function index()
    {
        $data = QueryBuilder::for(Setting::tenanted())
            ->allowedFilters(AllowedFilter::exact('company_id'))
            ->get();

        return DefaultResource::collection($data);
    }

    public function show(int $id)
    {
        $setting = Setting::findTenanted($id);
        return new DefaultResource($setting);
    }

    public function update(int $id, UpdateRequest $request)
    {
        $setting = Setting::findTenanted($id);
        $setting->update($request->validated());

        return (new DefaultResource($setting))->response()->setStatusCode(Response::HTTP_ACCEPTED);
    }
}
