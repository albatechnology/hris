<?php

namespace App\Http\Services\SupervisorRequestSchedule;

use App\Enums\ApprovalStatus;
use App\Http\Services\BaseService;
use App\Interfaces\Repositories\SupervisorRequestSchedule\SupervisorRequestScheduleRepositoryInterface;
use App\Interfaces\Services\SupervisorRequestSchedule\SupervisorRequestScheduleServiceInterface;
use App\Models\Schedule;
use Exception;
use Illuminate\Support\Facades\DB;

class SupervisorRequestScheduleService extends BaseService implements SupervisorRequestScheduleServiceInterface
{
    public function __construct(protected SupervisorRequestScheduleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Schedule
    {
        $data = array_merge($data, [
            'is_need_approval' => true,
            'approval_status' => ApprovalStatus::PENDING,
        ]);

        DB::beginTransaction();
        try {
            $schedule = $this->repository->create($data);

            $order = 1;
            foreach ($data['shifts'] ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
            return $schedule;
        } catch (Exception $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function update(string $id, array $data): bool
    {
        $schedule = $this->repository->findById($id);

        DB::beginTransaction();
        try {
            $schedule->shifts()->sync([]);
            $schedule->update($data);

            $order = 1;
            foreach ($data['shifts'] ?? [] as $shift) {
                $schedule->shifts()->attach($shift['id'], ['order' => $order++]);
            }

            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            throw $th;
        }

        return true;
    }

    public function approve(string $id, array $data): void
    {
        $schedule = $this->repository->findById($id);

        DB::beginTransaction();
        try {
            $schedule->update($data);
            DB::commit();
        } catch (Exception $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
