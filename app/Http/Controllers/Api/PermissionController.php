<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;

class PermissionController extends Controller
{
    public function all()
    {
        return response()->json(PermissionService::getAllPermissions());
    }
}
