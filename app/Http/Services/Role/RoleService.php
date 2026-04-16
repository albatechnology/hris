<?php

namespace App\Http\Services\Role;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Role\RoleRepositoryInterface;
use App\Interfaces\Services\Role\RoleServiceInterface;
use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RoleService extends BaseService implements RoleServiceInterface
{
    public function __construct(protected RoleRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Role
    {
        $permissionNames = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        $role = DB::transaction(function () use ($data, $permissionNames) {
            $data['guard_name'] = 'web';
            $role = $this->repository->create($data);
            $role->syncPermissions($permissionNames ?? []);
            return $role;
        });

        Artisan::call('permission:cache-reset');

        return $role;
    }

    public function update(string $id, array $data): bool
    {
        $permissionNames = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        DB::transaction(function () use ($id, $data, $permissionNames) {
            $data['guard_name'] = 'web';

            $role = $this->repository->findById($id);
            $role->update($data);
            $role->syncPermissions($permissionNames ?? []);
        });

        Artisan::call('permission:cache-reset');

        return true;
    }

    public function delete(string $id): bool
    {
        $role = $this->repository->findById($id);
        if ($role->id == 1) {
            throw new \Exception('Role administrator tidak dapat dihapus!');
        }

        return $this->repository->delete($id);
    }
}
