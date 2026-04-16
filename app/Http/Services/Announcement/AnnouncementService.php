<?php

namespace App\Http\Services\Announcement;

use App\Http\Services\BaseService;
use App\Interfaces\Repositories\Announcement\AnnouncementRepositoryInterface;
use App\Interfaces\Services\Announcement\AnnouncementServiceInterface;

class AnnouncementService extends BaseService implements AnnouncementServiceInterface
{
    public function __construct(protected AnnouncementRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
