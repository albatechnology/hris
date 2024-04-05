<?php

namespace App\Models;

class UserDepartmentPosition extends BaseModel
{
    protected $fillable = ['user_id', 'department_id', 'position_id'];

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
