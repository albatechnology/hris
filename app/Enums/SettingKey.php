<?php

namespace App\Enums;

use App\Rules\CompanyTenantedRule;

enum SettingKey: string
{
    use BaseEnum;

    case REQUEST_CHANGE_DATA_APPROVER = 'request_change_data_approver';

    public function getValueType(): mixed
    {
        return match ($this) {
            self::REQUEST_CHANGE_DATA_APPROVER => SettingValueType::MODEL,
            default => null,
        };
    }

    public function getSource(): mixed
    {
        return match ($this) {
            self::REQUEST_CHANGE_DATA_APPROVER => route('users.index'),
            default => null,
        };
    }

    public function getDefaultValue(): mixed
    {
        return match ($this) {
            default => null,
        };
    }

    public function getValidationRules(): mixed
    {
        return match ($this) {
            self::REQUEST_CHANGE_DATA_APPROVER => [
                'nullable',
                new CompanyTenantedRule(\App\Models\User::class, 'User not found')
            ],
            default => 'nullable',
        };
    }

    // public function getDescription(): string
    // {
    //     return match ($this) {
    //         self::REQUEST_CHANGE_DATA_APPROVER => 'REQUEST_CHANGE_DATA_approver',
    //         default => '',
    //     };
    // }
}
