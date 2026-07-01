<?php

namespace App\Http\Services\GuestBook;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\GuestBook\GuestBookRepositoryInterface;
use App\Interfaces\Services\GuestBook\GuestBookServiceInterface;
use App\Models\GuestBook;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GuestBookService extends BaseService implements GuestBookServiceInterface
{
    public function __construct(protected GuestBookRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): GuestBook
    {
        DB::beginTransaction();
        try {
            $guestBook = $this->repository->create($data);

            if (isset($data['files']) && is_array($data['files'])) {
                $manager = new ImageManager(new Driver());
                foreach ($data['files'] as $file) {
                    if ($file->isValid()) {
                        // Resize & compress
                        $optimized = $manager->read($file)
                            ->scaleDown(1280)
                            ->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 60));

                        // Upload hasil optimized langsung ke S3
                        $guestBook
                            ->addMediaFromStream($optimized)
                            ->usingFileName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg')
                            ->toMediaCollection(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_IN->value);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $guestBook;
    }

    public function update(string $id, array $data): bool
    {
        $guestBook = $this->repository->findById($id);

        DB::beginTransaction();
        try {
            $guestBook->update([
                'is_check_out' => true,
                'check_out_at' => now(),
                'check_out_by' => $data['check_out_by'] ?? auth()->id(),
            ]);

            if (isset($data['files']) && is_array($data['files'])) {
                $manager = new ImageManager(new Driver());

                foreach ($data['files'] as $file) {
                    if ($file->isValid()) {
                        // Resize & compress
                        $optimized = $manager->read($file)
                            ->scaleDown(1280)
                            ->encode(new \Intervention\Image\Encoders\JpegEncoder(quality: 60));

                        // Upload hasil optimized langsung ke S3
                        $guestBook
                            ->addMediaFromStream($optimized)
                            ->usingFileName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg')
                            ->toMediaCollection(\App\Enums\MediaCollection::GUEST_BOOK_CHECK_OUT->value);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }

    public function export(array $filters): Collection
    {
        $companyId = $filters['company_id'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $checkInStartDate = $filters['check_in_start_date'] ?? null;
        $checkInEndDate = $filters['check_in_end_date'] ?? null;

        return $this->repository->findAll(
            fn($q) => $q->tenanted()
                ->when($companyId, fn($q) => $q->whereHas('branch', fn($q) => $q->where('company_id', $companyId)))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when($checkInStartDate, fn($q) => $q->whereDate('created_at', '>=', $checkInStartDate))
                ->when($checkInEndDate, fn($q) => $q->whereDate('created_at', '<=', $checkInEndDate)),
            [],
            [
                'branch' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'user' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'checkOutBy' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'media'
            ]
        );
    }
}