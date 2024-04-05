<?php

namespace App\Models;

class UserDepartmentPosition extends BaseModel
{
    protected $fillable = ['user_id', 'department_id', 'position_id'];
}
