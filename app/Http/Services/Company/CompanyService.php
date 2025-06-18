<?php

namespace App\Http\Services\Company;

use App\Enums\SubscriptionKey;
use App\Http\Services\BaseService;
use App\Http\Services\Subscription\ValidateSubscriptionService;
use App\Interfaces\Repositories\Company\CompanyRepositoryInterface;
use App\Interfaces\Services\Company\CompanyServiceInterface;
use App\Models\Company;
use Exception;
use Illuminate\Support\Facades\DB;

class CompanyService extends BaseService implements CompanyServiceInterface
{
    public function __construct(protected CompanyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Company
    {
        (new ValidateSubscriptionService($data['group_id'], SubscriptionKey::COMPANIES))();

        DB::beginTransaction();
        try {
            $company = $this->repository->create($data);
            app(CompanyInitializeService::class)($company);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $company;
    }
}
