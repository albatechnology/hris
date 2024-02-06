<?php

namespace App\Enums;

enum JobLevel: string
{
    use BaseEnum;

    case STAFF = 'staff';
    case OPERATOR = 'operator';
    case LEADER = 'leader';
    case FOREMAN = 'foreman';
    case SUPERVISOR = 'supervisor';
    case ASSISTANT_MANAGER = 'asisten manager';
    case MANAGER = 'manager';
    case DIRECTOR = 'direktur';
}
