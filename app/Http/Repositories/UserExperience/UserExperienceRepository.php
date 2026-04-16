<?php

namespace App\Http\Repositories\UserExperience;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\UserExperience\UserExperienceRepositoryInterface;
use App\Models\UserExperience;

class UserExperienceRepository extends BaseRepository implements UserExperienceRepositoryInterface
{
    public function __construct(UserExperience $model)
    {
        parent::__construct($model);
    }

    public function findByUserAndId(int $userId, int $id)
    {
        return $this->model->where('user_id', $userId)->where('id', $id)->firstOrFail();
    }

    public function createForUser(int $userId, array $data)
    {
        return $this->model->create(array_merge($data, ['user_id' => $userId]));
    }
}
