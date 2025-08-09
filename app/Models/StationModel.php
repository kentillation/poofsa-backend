<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stations';

    protected $guarded = [];

    protected $primaryKey = 'station_id';

    protected $fillable = [
        'station_id', 'station_name',
    ];

    protected $visible = [
        'station_id', 'station_name',
    ];
}
