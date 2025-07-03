<?php

namespace App\Interfaces\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseServiceInterface
{
    public function findAll(?Closure $query = null): Collection;
    public function findById(string $id, ?Closure $query = null): ?Model;
    public function create(array $data): Model;
    public function update(string $id, array $data): bool;
    public function delete(string $id): bool;
    public function restore(string $id): bool;
    public function forceDelete(string $id): bool;
}
