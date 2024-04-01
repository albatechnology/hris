<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestChangeDataType;
use App\Http\Requests\Api\RequestChangeDataAllowes\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Models\RequestChangeDataAllowes;

class RequestChangeDataAllowesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // $this->middleware('permission:request_change_data_allowes_read', ['only' => 'index']);
        // $this->middleware('permission:request_change_data_allowes_edit', ['only' => 'update']);
    }

    public function index(int $companyId)
    {
        $requestChangeDataAllowes = RequestChangeDataAllowes::where('company_id', $companyId)->whereIn('type', RequestChangeDataType::getValues())->get();
        $requestChangeDataTypes = RequestChangeDataType::cases();

        $data = [];
        foreach ($requestChangeDataTypes as $type) {
            $requestChangeDataAllow = $requestChangeDataAllowes->firstWhere('type', $type);
            $data[] = [
                'type' => $type->value,
                'input_type' => $type->getInputType(),
                'input_value' => $type->getInputValue(),
                'is_active' => $requestChangeDataAllow?->is_active ?? false,
            ];
        }

        return $data;
    }

    public function store(StoreRequest $request, int $companyId)
    {
        $requestChangeDataAllowes = RequestChangeDataAllowes::where('company_id', $companyId)->where('type', $request->type)->get();

        $requestChangeDataAllow = null;
        if ($requestChangeDataAllowes->count() > 0) {
            $requestChangeDataAllow = $requestChangeDataAllowes[0];
        }
        $requestChangeDataAllowes->each->delete();

        if (!$requestChangeDataAllow) {
            $requestChangeDataAllow = new RequestChangeDataAllowes();
            $requestChangeDataAllow->company_id = $companyId;
        }

        $requestChangeDataAllow->type = $request->type;
        $requestChangeDataAllow->is_active = $request->is_active ?? false;
        $requestChangeDataAllow->updated_at = now();
        $requestChangeDataAllow->save();

        return new DefaultResource($requestChangeDataAllow);
    }
}
