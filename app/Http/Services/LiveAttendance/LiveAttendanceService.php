<?php

namespace App\Http\Services\LiveAttendance;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\LiveAttendance\LiveAttendanceRepositoryInterface;
use App\Interfaces\Services\LiveAttendance\LiveAttendanceServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Exception;
use Illuminate\Support\Facades\DB;

class LiveAttendanceService extends BaseService implements LiveAttendanceServiceInterface
{
    public function __construct(protected LiveAttendanceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function createWithRelations(array $data, array $locations = [], array $userIds = [])
    {
        DB::beginTransaction();
        try {
            $model = $this->repository->createWithRelations($data, $locations, $userIds);
            DB::commit();

            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateWithRelations(int|string $id, array $data, array $locations = [], array $userIds = [])
    {
        DB::beginTransaction();
        try {
            $model = $this->repository->updateWithRelations($id, $data, $locations, $userIds);
            DB::commit();

            return $model;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
