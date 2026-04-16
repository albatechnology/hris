<?php

namespace App\Http\Repositories\Announcement;

use App\Http\Repositories\BaseRepository;
use App\Interfaces\Repositories\Announcement\AnnouncementRepositoryInterface;
use App\Models\Announcement;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    public function __construct(Announcement $model)
    {
        parent::__construct($model);
    }
}
