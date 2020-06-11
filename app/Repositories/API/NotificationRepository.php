<?php

namespace App\Repositories\API;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;

/**
 * Class NotificationRepository.
 */
class NotificationRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
      return Notification::class;
    }

    public function getBy($field, $value)
    {
        return $this->model
            ->where($field, $value)
            ->first();
    }
}