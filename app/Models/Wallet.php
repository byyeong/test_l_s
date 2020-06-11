<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'wallet';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['travel_id', 'date' ,'price', 'currency_id', 'payment', 'title', 'text', 'used_type', 'gmt'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = ['price' => 'double', 'currency_id' => 'integer', 'used_type' => 'integer', 'travel_id' => 'integer', 'gmt' => 'string'];

    public function files()
    {
        return $this->morphMany('App\Models\ToolFile', 'tool');
    }

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency');
    }
}
