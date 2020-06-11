<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LodaExt extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'loda_ext';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['loda_id', 'data'];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function loda()
    {
        return $this->morphTo();
    }
}
