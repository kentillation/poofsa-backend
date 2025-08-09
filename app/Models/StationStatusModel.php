<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationStatusModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_station_status';

    protected $guarded = [];

    protected $primaryKey = 'station_status_id';

    protected $keyType = 'int';
}
