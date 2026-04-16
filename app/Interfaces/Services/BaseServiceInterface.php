<?php

namespace App\Interfaces\Services;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

interface BaseServiceInterface
{
    /**
     * Get all paginated data with optional query modifications.
     *
     * @param int $perPage Number of items per page
     * @param Closure|null $query Optional closure to modify query
     * @param array $allowedFilters Allowed filters for query builder
     * @param array $allowedIncludes Allowed includes for query builder
     * @param array $allowedFields Allowed fields for query builder
     * @param array $allowedSorts Allowed sorts for query builder
     * @param bool $isSimplePaginate Whether to use simple pagination
     *
     * @return LengthAwarePaginator|Paginator
     */
    public function findAllPaginate(int $perPage = 15, ?Closure $query = null, ?array $allowedFilters = [], ?array $allowedIncludes = [], ?array $allowedSorts = [], ?array $allowedFields = [],  bool $isSimplePaginate = false): LengthAwarePaginator|Paginator;
    public function findAll(?Closure $query = null, ?array $allowedFilters = [], ?array $allowedIncludes = [], ?array $allowedSorts = [], ?array $allowedFields = []): Collection;
    public function findById(string $id, ?Closure $query = null, ?array $load = []): ?Model;
    public function findByIdOrFail(string $id, ?Closure $query = null, ?array $load = []): Model|Exception;
    public function create(array $data): Model;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function forceDelete(string $id): bool;
    public function restore(string $id): bool;

    // public function findAll(?Closure $query = null): Collection;
    // public function findById(string $id, ?Closure $query = null): ?Model;
    // public function create(array $data): Model;
    // public function update(string $id, array $data): bool;
    // public function delete(string $id): bool;
    // public function restore(string $id): bool;
    // public function forceDelete(string $id): bool;
}
