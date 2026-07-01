<?php

namespace App\Http\Repositories\UserEducation;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\UserEducation\UserEducationRepositoryInterface;
use App\Models\UserEducation;
use Spatie\QueryBuilder\QueryBuilder;

class UserEducationRepository extends BaseRepository implements UserEducationRepositoryInterface
{
    public function __construct(UserEducation $model)
    {
        parent::__construct($model);
    }

    public function findByUser(int $userId)
    {
        return QueryBuilder::for($this->model->where('user_id', $userId))
            ->allowedFilters([
                'type',
                'level',
                'name',
                'institution_name',
                'majors',
                'start_date',
                'end_date',
                'expired_date',
                'score',
                'fee',
            ])
            ->allowedSorts([
                'id',
                'name',
                'institution_name',
                'majors',
                'start_date',
                'end_date',
                'expired_date',
                'score',
                'fee',
                'created_at',
            ])
            ->paginate(15); // Use default per_page or pass it
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
