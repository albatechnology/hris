<?php

namespace App\Http\Services\Branch;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Branch\BranchRepositoryInterface;
use App\Interfaces\Services\Branch\BranchServiceInterface;
use App\Models\Branch;
use Exception;
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
}
