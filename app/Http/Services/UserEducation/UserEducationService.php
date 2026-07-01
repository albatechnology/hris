<?php

namespace App\Http\Services\UserEducation;

use App\Enums\MediaCollection;
use App\Http\Services\BaseService;
use App\Interfaces\Repositories\UserEducation\UserEducationRepositoryInterface;
use App\Interfaces\Services\UserEducation\UserEducationServiceInterface;
use App\Models\UserEducation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UserEducationService extends BaseService implements UserEducationServiceInterface
{
    public function __construct(protected UserEducationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function listByUser(int $userId)
    {
        return $this->repository->findByUser($userId);
    }

    public function findByUser(int $userId, int $id)
    {
        return $this->repository->findByUserAndId($userId, $id);
    }

    public function createForUser(int $userId, array $data)
    {
        DB::beginTransaction();

        try {
            $education = $this->repository->createForUser($userId, $data);

            if (isset($data['file']) && $data['file'] instanceof UploadedFile && $data['file']->isValid()) {
                $mediaCollection = MediaCollection::USER_EDUCATION->value;
                $education->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            DB::commit();

            return $education;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateForUser(int $userId, int $id, array $data)
    {
        $education = $this->findByUser($userId, $id);

        DB::beginTransaction();

        try {
            $this->repository->update($id, $data);

            if (isset($data['file']) && $data['file'] instanceof UploadedFile && $data['file']->isValid()) {
                $mediaCollection = MediaCollection::USER_EDUCATION->value;
                $education->clearMediaCollection($mediaCollection);
                $education->addMediaFromRequest('file')->toMediaCollection($mediaCollection);
            }

            DB::commit();

            return $education;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
