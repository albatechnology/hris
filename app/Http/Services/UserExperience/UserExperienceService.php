<?php

namespace App\Http\Services\UserExperience;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\UserExperience\UserExperienceRepositoryInterface;
use App\Interfaces\Services\UserExperience\UserExperienceServiceInterface;

class UserExperienceService extends BaseService implements UserExperienceServiceInterface
{
    public function __construct(protected UserExperienceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function findByUser(int $userId, int $id)
    {
        return $this->repository->findByUserAndId($userId, $id);
    }

    public function createForUser(int $userId, array $data)
    {
        return $this->repository->createForUser($userId, $data);
    }

    public function updateForUser(int $userId, int $id, array $data)
    {
        $experience = $this->findByUser($userId, $id);
        $this->repository->update($id, $data);
        return $experience;
    }
}
