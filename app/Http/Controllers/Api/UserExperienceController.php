<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UserExperience\StoreRequest;
use App\Http\Resources\DefaultResource;
use App\Interfaces\Services\UserExperience\UserExperienceServiceInterface;
use App\Models\User;
use App\Models\UserExperience;
use Spatie\QueryBuilder\AllowedFilter;

class UserExperienceController extends BaseController
{
    public function __construct(private UserExperienceServiceInterface $service)
    {
        parent::__construct();
    }

    public function index(int $userId)
    {
        $user = User::findTenanted($userId);
        $datas = $this->service->findAllPaginate(
            $this->per_page,
            fn($q) => $q->where('user_id', $user->id),
            [
                AllowedFilter::exact('user_id'),
                'company',
                'department',
                'position',
                'start_date',
                'end_date',
            ],
            [],
            [
                'id',
                'user_id',
                'company',
                'department',
                'position',
                'start_date',
                'end_date',
                'created_at',
            ],
        );

        return DefaultResource::collection($datas);
    }

    public function show(int $userId, UserExperience $experience)
    {
        $data = $this->service->findByUser($userId, $experience->id);
        return new DefaultResource($data);
    }

    public function store(int $userId, StoreRequest $request)
    {
        $this->service->createForUser($userId, $request->validated());

        return $this->createdResponse();
    }

    public function update(int $userId, StoreRequest $request, UserExperience $experience)
    {
        $data = $this->service->findByUser($userId, $experience->id);
        $this->service->updateForUser($userId, $experience->id, $request->validated());

        return $this->updatedResponse();
    }

    public function destroy(int $userId, UserExperience $experience)
    {
        $data = $this->service->findByUser($userId, $experience->id);
        $this->service->delete($experience->id);

        return $this->deletedResponse();
    }
}
