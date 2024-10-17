<?php

namespace App\Interfaces;

use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

interface Requested
{
    public function approvals(): MorphMany;
    public function scopeMyApprovals(Builder $query): void;
    public function scopeWhereApprovalStatus(Builder $query, string|ApprovalStatus $status = ApprovalStatus::PENDING->value): Builder;
    public function getApprovalStatusAttribute(): string;
    public function isDescendantApproved(): mixed;
    public function checkAndUpdate(): void;
    public function sendRequestNotification(User $receiver, User $requester): void;
    public function sendApprovedNotification(User $sender, ApprovalStatus $approvalStatus): void;
}
