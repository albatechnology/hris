<?php

namespace App\Http\Services\Reimbursement;

use App\Enums\MediaCollection;
use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Reimbursement\ReimbursementRepositoryInterface;
use App\Interfaces\Services\Reimbursement\ReimbursementServiceInterface;
use App\Interfaces\Services\ReimbursementCategory\ReimbursementCategoryServiceInterface;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ReimbursementService extends BaseService implements ReimbursementServiceInterface
{
    public function __construct(
        private ReimbursementRepositoryInterface $repository,
        private ReimbursementCategoryServiceInterface $reimbursementCategoryService,
    ) {
        parent::__construct($repository);
    }

    public function create(array $data): Model
    {
        $user = User::findOrFail($data['user_id']);

        $reimbursementCategory = $this->reimbursementCategoryService->findById($data['reimbursement_category_id']);
        list($startDate, $endDate) = $this->reimbursementCategoryService->getStartEndDate($reimbursementCategory, $data['date']);
        $limitAmount = $this->reimbursementCategoryService->getLimitAmount($reimbursementCategory, $user);
        $totalAmountReimbursementTaken = $this->getTotalReimbursementTaken($user, $reimbursementCategory, $startDate, $endDate);

        if ($totalAmountReimbursementTaken + floatval($data['amount']) > $limitAmount) {
            throw new UnprocessableEntityHttpException('Your remaining reimbursement limit is only ' . number_format(max($limitAmount - $totalAmountReimbursementTaken, 0), 2));
        }

        DB::beginTransaction();
        try {
            $reimbursement = Reimbursement::create($data);

            foreach ($data['files'] as $file) {
                if ($file->isValid()) {
                    $reimbursement->addMedia($file)->toMediaCollection(MediaCollection::REIMBURSEMENT->value);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $reimbursement;
    }

    /**
     * Get the total amount of reimbursement taken by a user on a reimbursement category,
     * within the given date range.
     *
     * @param User|int $userId
     * @param ReimbursementCategory|int $reimbursementCategoryId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return int
     */
    public function getTotalReimbursementTaken(User|int $userId, ReimbursementCategory|int|null $reimbursementCategoryId = null, ?string $startDate = null, ?string $endDate = null): int
    {
        if ($userId instanceof User) {
            $userId = $userId->id;
        }

        if ($reimbursementCategoryId && $reimbursementCategoryId instanceof ReimbursementCategory) {
            $reimbursementCategoryId = $reimbursementCategoryId->id;
        }

        return Reimbursement::where('user_id', $userId)
            ->when($reimbursementCategoryId, fn($q) => $q->where('reimbursement_category_id', $reimbursementCategoryId))
            ->approved()
            ->when($startDate, fn($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('date', '<=', $endDate))
            ->sum('amount');
    }
}
