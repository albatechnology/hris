<?php

namespace App\Classes;

use App\Models\User;
use App\Models\UserSupervisor;
use Illuminate\Support\Collection;

class SupervisorUtility
{
    protected Collection $supervisorLines;

    protected function __construct(protected User $user, protected User $supervisor)
    {
        $this->supervisorLines = $this->user->supervisors()->where('is_additional_supervisor', 0)->orderBy('order')->get();
    }

    public static function build(User $user, User $supervisor)
    {
        return new self($user, $supervisor);
    }

    protected function getSupervisorOrder(): int|null
    {
        $index = $this->supervisorLines->search(fn($supervisorLine) => $supervisorLine->supervisor_id == $this->supervisor->id);
        if ($index === false) return null;

        return $index;
    }

    public function getSupervisor(bool $isAscendant = true, bool $isLoadUser = true): UserSupervisor|null
    {
        $order = $this->getSupervisorOrder();
        if (is_null($order)) return null;

        if ($isAscendant) {
            $order++;
        } else {
            $order--;
        }

        $supervisor = $this->supervisorLines[$order] ?? null;
        if (!$supervisor) return null;

        if ($isLoadUser) $supervisor->load(['supervisor' => fn($q) => $q->select('id', 'fcm_token', 'email', 'name')]);

        return $supervisor;
    }

    public function getSupervisorSubordinates(): Collection
    {
        $supervisor = $this->supervisorLines->firstWhere('supervisor_id', $this->supervisor->id);
        if (!$supervisor) {
            return collect([]);
        }

        $data = $this->supervisorLines->where('order', '<', $supervisor->order);
        return $data;
    }
    // ENABLE THIS METHOD IF NEEDED
    // public function getTopAscendant(bool $isLoadUser = true): UserSupervisor|null
    // {
    //     $totalSupervisor = $this->supervisorLines->count();
    //     if ($totalSupervisor == 0) return null;

    //     $supervisor = $this->supervisorLines[$totalSupervisor - 1];
    //     if (!$supervisor) return null;

    //     if ($isLoadUser) $supervisor->load(['supervisor' => fn($q) => $q->select('id', 'fcm_token', 'email', 'name')]);

    //     return $supervisor;
    // }
}
