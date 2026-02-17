<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_unit';

    protected $guarded = [];

    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'unit_id', 'unit_label', 'unit_avb'
    ];

    protected $visible = [
        'unit_id', 'unit_label', 'unit_avb'
    ];
}
