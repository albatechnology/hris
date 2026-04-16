<?php

namespace App\Http\Services\UserCustomField;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\UserCustomField\UserCustomFieldRepositoryInterface;
use App\Interfaces\Services\UserCustomField\UserCustomFieldServiceInterface;
use App\Models\CustomField;
use App\Models\User;
use App\Models\UserCustomField;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserCustomFieldService extends BaseService implements UserCustomFieldServiceInterface
{
    public function __construct(protected UserCustomFieldRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function listByUser(int $userId)
    {
        $user = User::findTenanted($userId);
        $customFields = CustomField::tenanted()->get();

        return $customFields->map(function ($customField) use ($user) {
            $userCustomField = $user->customFields()->where('custom_field_id', $customField->id)->first();
            $customField->custom_field_id = $customField->id;
            $customField->id = null;
            $customField->value = null;

            if ($userCustomField) {
                $customField->id = $userCustomField->id;
                $customField->value = $userCustomField->value;
            }

            return $customField;
        });
    }

    public function findByUser(int $userId, int $id)
    {
        $userCustomField = $this->repository->findByUserAndId($userId, $id);

        if (!$userCustomField) {
            throw new NotFoundHttpException('Data not found');
        }

        return $userCustomField;
    }

    public function createForUser(int $userId, array $data)
    {
        if ($this->repository->existsByUserAndCustomField($userId, $data['custom_field_id'])) {
            throw new \Exception('Custom field already exists');
        }

        return $this->repository->createForUser($userId, $data);
    }

    public function updateForUser(int $userId, int $id, array $data)
    {
        $userCustomField = $this->findByUser($userId, $id);

        DB::beginTransaction();

        try {
            $userCustomField->update($data);
            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
