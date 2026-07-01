<?php

namespace App\Http\Repositories\GuestBook;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\GuestBook\GuestBookRepositoryInterface;
use App\Models\GuestBook;

class GuestBookRepository extends BaseRepository implements GuestBookRepositoryInterface
{
    public function __construct(GuestBook $model)
    {
        parent::__construct($model);
    }
}