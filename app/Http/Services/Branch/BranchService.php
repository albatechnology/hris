<?php

namespace App\Http\Services\Branch;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Branch\BranchRepositoryInterface;
use App\Interfaces\Services\Branch\BranchServiceInterface;
use App\Models\Branch;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchService extends BaseService implements BranchServiceInterface
{
    public function __construct(protected BranchRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Branch
    {
        // (new ValidateSubscriptionService($data['group_id'], SubscriptionKey::COMPANIES))();

        DB::beginTransaction();
        try {
            $branch = $this->repository->create($data);
            app(BranchInitializeService::class)($branch);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $branch;
    }

    public function summary(?int $branchId): array
    {
        return Cache::remember('branch_summary_' . ($branchId ?? 'null'), now()->addSecond(), function () use ($branchId) {
            if (empty($branchId)) {
                return [
                    'branch' => 0,
                    'client' => 0,
                    'users' => 0,
                ];
            }

            $branch = $this->repository->findBranchForSummary($branchId);

            if (!$branch) {
                return [
                    'branch' => 0,
                    'client' => 0,
                    'users' => 0,
                ];
            }

            return [
                'branch' => $branch->is_main ? $this->repository->countParentBranches() : 0,
                'client' => $this->repository->countClients($branchId, $branch->is_main),
                'users' => User::tenanted()->where('branch_id', $branchId)->whereNull('resign_date')->count(),
            ];
        });
    }
}
