<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_shop_station';

    protected $primaryKey = 'shop_station_id';

    protected $fillable = [
        'shop_station_id', 'station_name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
