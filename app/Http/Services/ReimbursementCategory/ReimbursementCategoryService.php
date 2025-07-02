<?php

namespace App\Http\Services\ReimbursementCategory;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\ReimbursementCategory\ReimbursementCategoryRepositoryInterface;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;

class ReimbursementCategoryService extends BaseService implements ReimbursementCategoryServiceInterface
{
    public function __construct(protected ReimbursementCategoryRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    // public function create(array $data): ReimbursementCategory
    // {
    //     // (new ValidateSubscriptionService($data['group_id'], SubscriptionKey::COMPANIES))();

    //     DB::beginTransaction();
    //     try {
    //         $ReimbursementCategory = $this->repository->create($data);
    //         app(ReimbursementCategoryInitializeService::class)($ReimbursementCategory);

    //         DB::commit();
    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }

    //     return $ReimbursementCategory;
    // }
}
