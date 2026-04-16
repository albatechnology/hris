<?php

namespace App\Http\Services\Loan;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Loan\LoanRepositoryInterface;
use App\Interfaces\Services\Loan\LoanServiceInterface;
use App\Models\Loan;
use Exception;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class LoanService extends BaseService implements LoanServiceInterface
{
    public function __construct(protected LoanRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Loan
    {
        DB::beginTransaction();

        try {
            $details = $data['details'] ?? [];
            unset($data['details']);

            $loan = $this->repository->create($data);
            if (!empty($details)) {
                $loan->details()->createMany($details);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $loan;
    }

    public function update(string $id, array $data): bool
    {
        DB::beginTransaction();

        try {
            $loan = $this->findByIdOrFail($id);

            $details = $data['details'] ?? [];
            unset($data['details']);

            $amountPaid = $loan->details->whereNotNull('run_payroll_user_id')->sum('basic_payment');
            $newTotalAmount = collect($details)->sum('basic_payment');
            if ($amountPaid + $newTotalAmount != $data['amount']) {
                throw new UnprocessableEntityHttpException('Payments is not correct with total amount');
            }

            $loan->update($data);
            $loan->details()->whereNull('run_payroll_user_id')->delete();
            if (!empty($details)) {
                $loan->details()->createMany($details);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }
}
