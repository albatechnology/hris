<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserEducation\StoreRequest;
use App\Http\Resources\UserEducation\UserEducationResource;
use App\Interfaces\Services\UserEducation\UserEducationServiceInterface;
use App\Models\UserEducation;

class UserEducationController extends BaseController
{
    public function __construct(private UserEducationServiceInterface $service)
    {
        parent::__construct();
    }

    public function index(int $id)
    {
        $datas = $this->service->listByUser($id);

        return UserEducationResource::collection($datas);
    }

    public function show(int $id, UserEducation $education)
    {
        $userEducation = $this->service->findByUser($id, $education->id);
        return new UserEducationResource($userEducation);
    }

    public function store(int $id, StoreRequest $request)
    {
        try {
            $this->service->createForUser($id, $request->validated());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->createdResponse();
    }

    public function update(int $id, StoreRequest $request, UserEducation $education)
    {
        $this->service->findByUser($id, $education->id);
        try {
            $this->service->updateForUser($id, $education->id, $request->validated());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        return $this->updatedResponse();
    }

    public function destroy(int $id, UserEducation $education)
    {
        $this->service->findByUser($id, $education->id);
        $this->service->delete($education->id);

        return $this->deletedResponse();
    }
}
