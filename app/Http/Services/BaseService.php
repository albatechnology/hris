<?php

namespace App\Http\Services;

use App\Interfaces\Repositories\BaseRepositoryInterface;
use App\Interfaces\Services\BaseServiceInterface;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseService implements BaseServiceInterface
{
    public function __construct(protected BaseRepositoryInterface $baseRepository) {}

    public function findAll(?Closure $query = null): Collection
    {
        return $this->baseRepository->findAll();
    }

    public function findById(string $id, ?Closure $query = null, bool $withTrashed = false): ?Model
    {
        return $this->baseRepository->findById($id, $query, $withTrashed);
    }

    public function create(array $data): Model
    {
        return $this->baseRepository->create($data);
    }

    public function update(string $id, array $data): bool
    {
        return $this->baseRepository->update($id, $data);
    }

    public function delete(string $id): bool
    {
        return $this->baseRepository->delete($id);
    }

    public function restore(string $id): bool
    {
        return $this->baseRepository->restore($id);
    }

    public function forceDelete(string $id): bool
    {
        return $this->baseRepository->forceDelete($id);
    }
}
