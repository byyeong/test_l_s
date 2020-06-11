<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserAdditional.
 */
class UserAdditional extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_detail_info';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id', 'gender', 'birth', 'available_email', 'phone', 'job', 'area', 'age_range'];
}
