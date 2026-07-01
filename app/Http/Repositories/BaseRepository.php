<?php

namespace App\Http\Repositories;

use App\Interfaces\Repositories\BaseRepositoryInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function __construct(protected Model $model) {}

    protected function query(): Builder
    {
        if (method_exists($this->model, 'scopeTenanted')) {
            return $this->model->query()->tenanted();
        }

        // return $this->model->query();
        return $this->model->newQuery();
    }

    // public function findAll(?Closure $query = null): Collection
    // {
    //     return $this->query()
    //         ->when($query, $query)
    //         ->get();
    // }
    // public function findById(string $id, ?Closure $query = null, bool $withTrashed = false): ?Model
    // {
    //     return $this->query()
    //         ->when($query, $query)
    //         ->when($withTrashed, fn($q) => $q->withTrashed())->where('id', $id)->firstOrFail();
    // }

    public function findAllPaginate(int $perPage = 15, ?Closure $query = null, ?array $allowedFilters = [], ?array $allowedIncludes = [], ?array $allowedSorts = [], ?array $allowedFields = [], bool $isSimplePaginate = false): LengthAwarePaginator|Paginator
    {
        $query = QueryBuilder::for(
            $this->query()->when($query, $query)
        );

        if (count($allowedFields)) {
            $query->allowedFields($allowedFields);
        }

        if (count($allowedFilters)) {
            $query->allowedFilters($allowedFilters);
        }

        if (count($allowedIncludes)) {
            $query->allowedIncludes($allowedIncludes);
        }

        if (count($allowedSorts)) {
            $query->allowedSorts($allowedSorts);
        }

        if ($isSimplePaginate) {
            return $query->simplePaginate($perPage)->withQueryString();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findAll(?Closure $query = null, ?array $allowedFilters = [], ?array $allowedIncludes = [], ?array $allowedSorts = [], ?array $allowedFields = []): Collection
    {
        $query = QueryBuilder::for(
            $this->query()->when($query, $query)
        );

        if (count($allowedFields)) {
            $query->allowedFields($allowedFields);
        }

        if (count($allowedFilters)) {
            $query->allowedFilters($allowedFilters);
        }

        if (count($allowedIncludes)) {
            $query->allowedIncludes($allowedIncludes);
        }

        if (count($allowedSorts)) {
            $query->allowedSorts($allowedSorts);
        }

        return $query->get();
    }

    public function findById(string $id, ?Closure $query = null, ?array $load = []): ?Model
    {
        $data = $this->query()
            ->when($query, $query)
            ->find($id);

        if ($data && count($load)) {
            $data = $data->load($load);
        }

        return $data;
    }

    public function create(array $data): Model
    {
        return $this->model::create($data);
    }

    public function update(string $id, array $data): bool
    {
        return $this->query()->where('id', $id)->update($data);
    }

    public function delete(string $id): bool
    {
        $data = $this->findById($id, fn($q) => $q->select('id'));
        if (!$data) {
            throw new NotFoundHttpException("Data not found");
        }

        $data->delete();
        return true;
        // return $this->query()->where('id', $id)->delete();
    }

    public function restore(string $id): bool
    {
        return $this->query()->withTrashed()->where('id', $id)->restore();
    }

    public function forceDelete(string $id): bool
    {
        $data = $this->findById($id, fn($q) => $q->withTrashed()->select('id'));
        if (!$data) {
            throw new NotFoundHttpException("Data not found");
        }

        return $data->forceDelete();
        // return $this->query()->withTrashed()->where('id', $id)->forceDelete();
    }
}
