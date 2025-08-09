<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoundTypeModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_stocks_bound_type';
    protected $guarded = [];
}
