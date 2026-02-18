<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilityModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_availability';

    protected $primaryKey = 'availability_id';

    protected $fillable = [
        'availability_id', 'availability_label',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
