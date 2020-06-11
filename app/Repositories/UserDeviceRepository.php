<?php

namespace App\Repositories;

//use App\Models\UserDevice;
use Illuminate\Support\Facades\DB;
use App\Exceptions\GeneralException;
use App\Repositories\BaseRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class UserDeviceRepository.
 */
class UserDeviceRepository extends BaseRepository
{
  /**
   * @return string
   */
  public function model()
  {
    //return UserDevice::class;
  }

  /**
   * @param array $data
   *
   * @return bool
   */
  public function create(array $data) : UserDevice
  {
    $res = false;
    if ( !$this->userDeviceExists($data)) {
      $device = parent::create($data);

      if ($device) {
        $res = true;
      }
    }
    return $res;
  }

  /**
   * @param array
   *
   * @return bool
   */
  protected function userDeviceExists($data) : bool
  {
    return $this->model
      ->where('user_id', $data['user_id'])
      ->where('device_id', $data['device_id'])
      ->count() > 0;
  }
}