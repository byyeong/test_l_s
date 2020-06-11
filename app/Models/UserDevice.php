<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserDevice.
 */
class UserDevice extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_device';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'device_id', 'device_type', 'last_login_at'];
}
