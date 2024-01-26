<?php

namespace App\Models;

class UserBranch extends BaseModel
{
    public $incrementing = false;
    protected $fillable = ['user_id', 'branch_id'];
}
