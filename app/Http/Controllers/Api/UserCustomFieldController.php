<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserCustomField\StoreRequest;
use App\Http\Requests\Api\UserCustomField\UpdateRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\UserCustomField\UserCustomFieldServiceInterface;
use App\Models\UserCustomField;
use Illuminate\Http\Response;

class UserCustomFieldController extends BaseController
{
    public function __construct(private UserCustomFieldServiceInterface $service)
    {
        parent::__construct();
    }

    public function index(int $id)
    {
        $datas = $this->service->listByUser($id);

        return DefaultResource::collection($datas);
    }

    public function show(int $id, UserCustomField $customField)
    {
        $userCustomField = $this->service->findByUser($id, $customField->id);
        return new DefaultResource($userCustomField);
    }

    public function store(int $id, StoreRequest $request)
    {
        try {
            $this->service->createForUser($id, $request->validated());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), code: Response::HTTP_BAD_REQUEST);
        }

        return $this->createdResponse();
    }

    public function update(int $id, UserCustomField $customField, UpdateRequest $request)
    {
        $userCustomField = $this->service->findByUser($id, $customField->id);
        $this->service->updateForUser($id, $customField->id, $request->validated());

        return $this->updatedResponse();
    }
}
