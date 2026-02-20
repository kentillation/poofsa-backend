<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_ingredient_unit';

    protected $primaryKey = 'ingredient_unit_id';

    protected $fillable = [
        'unit_label',
        'unit_avb'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
