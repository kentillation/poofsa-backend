<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvailabilityModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_availability';

    protected $guarded = [];

    protected $primaryKey = 'availability_id';
    
    protected $fillable = [
        'availability_id', 'availability_label',
    ];
    
    protected $visible = [
        'availability_id', 'availability_label',
    ];
}
