<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_size';

    protected $guarded = [];

    protected $primaryKey = 'size_id';

    protected $fillable = [
        'size_id', 'size_label',
    ];

    protected $visible = [
        'size_id', 'size_label',
    ];
}
