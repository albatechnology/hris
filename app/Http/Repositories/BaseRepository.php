<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\BaseRepositoryInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    protected function query(): Builder
    {
        if (method_exists($this->model, 'scopeTenanted')) {
            return $this->model->query()->tenanted();
        }

        return $this->model->query();
    }

    public function findAll(?Closure $query = null): Collection
    {
        return $this->query()
            ->when($query, $query)
            ->get();
    }
    public function findById(string $id, ?Closure $query = null, bool $withTrashed = false): ?Model
    {
        return $this->query()
            ->when($query, $query)
            ->when($withTrashed, fn($q) => $q->withTrashed())->where('id', $id)->firstOrFail();
    }

    public function create(array $data): Model
    {
        return $this->model::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return $this->model::where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        return $this->model::where('id', $id)->delete();
    }

    public function restore(string $id): bool
    {
        return $this->query()->withTrashed()->where('id', $id)->restore();
    }

    public function forceDelete(string $id): bool
    {
        return $this->query()->withTrashed()->where('id', $id)->forceDelete();
    }
}
