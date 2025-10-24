<?php

namespace App\Http\Services\DailyActivity;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\DailyActivity\DailyActivityRepositoryInterface;
use App\Interfaces\Services\DailyActivity\DailyActivityServiceInterface;
use App\Models\DailyActivity;
use Illuminate\Support\Facades\DB;

class DailyActivityService extends BaseService implements DailyActivityServiceInterface
{
    public function __construct(protected DailyActivityRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): DailyActivity
    {
        return DB::transaction(function () use ($data) {
            $model = $this->repository->create($data);

            if (isset($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $image) {
                    if ($image->isValid()) {
                        $model->addMedia($image)->toMediaCollection();
                    }
                }
            }

            return $model;
        });
    }

    public function update(string $id, array $data): bool
    {
        $model = $this->repository->findById($id);
        if (!$model) {
            return false;
        }

        return DB::transaction(function () use ($model, $data) {
            $this->repository->update($model->id, $data);

            if (isset($data['images']) && is_array($data['images'])) {
                // Optional: Clear existing images if needed
                // $model->clearMediaCollection('images');

                foreach ($data['images'] as $image) {
                    if ($image->isValid()) {
                        $model->addMedia($image)->toMediaCollection();
                    }
                }
            }

            return true;
        });
    }
}
