<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemperatureModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_temp';

    protected $guarded = [];

    protected $primaryKey = 'temp_id';

    protected $fillable = [
        'temp_id', 'temp_label',
    ];

    protected $visible = [
        'temp_id', 'temp_label',
    ];
    
}
