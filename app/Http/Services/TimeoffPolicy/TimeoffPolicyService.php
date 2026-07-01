<?php

namespace App\Http\Services\TimeoffPolicy;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\TimeoffPolicy\TimeoffPolicyRepositoryInterface;
use App\Interfaces\Services\TimeoffPolicy\TimeoffPolicyServiceInterface;
use App\Models\TimeoffPolicy;
use Illuminate\Support\Facades\DB;

class TimeoffPolicyService extends BaseService implements TimeoffPolicyServiceInterface
{
    public function __construct(protected TimeoffPolicyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): TimeoffPolicy
    {
        DB::beginTransaction();

        try {
            $timeoffPolicy = $this->repository->create($data);

            if (isset($data['user_ids']) && count($data['user_ids']) > 0) {
                $timeoffPolicy->users()->sync($data['user_ids']);
            }

            DB::commit();

            return $timeoffPolicy;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function update(string $id, array $data): bool
    {
        $timeoffPolicy = $this->findByIdOrFail($id);

        DB::beginTransaction();

        try {
            $this->repository->update($id, $data);

            if (isset($data['user_ids']) && count($data['user_ids']) > 0) {
                $timeoffPolicy->users()->sync($data['user_ids']);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
