<?php

namespace App\Http\Repositories\UserCustomField;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\UserCustomField\UserCustomFieldRepositoryInterface;
use App\Models\UserCustomField;

class UserCustomFieldRepository extends BaseRepository implements UserCustomFieldRepositoryInterface
{
    public function __construct(UserCustomField $model)
    {
        parent::__construct($model);
    }

    public function findByUserAndId(int $userId, int $id)
    {
        return $this->model->where('user_id', $userId)->where('id', $id)->first();
    }

    public function existsByUserAndCustomField(int $userId, int $customFieldId): bool
    {
        return $this->model->where('user_id', $userId)->where('custom_field_id', $customFieldId)->exists();
    }

    public function createForUser(int $userId, array $data)
    {
        return $this->model->create(array_merge($data, ['user_id' => $userId]));
    }
}
