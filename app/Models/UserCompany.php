<?php

namespace App\Models;

class UserCompany extends BaseModel
{
    public $incrementing = false;
    protected $fillable = ['user_id', 'company_id'];
}
