<?php

namespace App\Interfaces\Repositories;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    public function findAll(?Closure $query = null): Collection;
    public function findById(string $id, ?Closure $query = null, bool $withTrashed = false): ?Model;
    public function create(array $data): Model;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function restore(string $id): bool;
    public function forceDelete(string $id): bool;
}
