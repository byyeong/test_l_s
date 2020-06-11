<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TravelPackingsCategories extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'travel_packings_categories';
    protected $casts = ['travel_id' => 'integer', 'qty' => 'integer', 'checked' => 'integer'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['packings_id', 'categories_id', 'travel_id', 'checked', 'qty'];
    protected $hidden = ['created_at', 'updated_at', 'ord'];
    public function packing()
    {
        return $this->belongsTo('App\Models\Packings', 'packings_id');
    }
    public function category()
    {
        return $this->belongsTo('App\Models\Categories', 'categories_id');
    }
}
