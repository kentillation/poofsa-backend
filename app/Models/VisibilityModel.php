<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisibilityModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_visibility';
    protected $guarded = [];
    protected $primaryKey = 'visibility_id';
    protected $fillable = [
        'visibility_id', 'visibility_label',
    ];
    protected $visible = [
        'visibility_id', 'visibility_label',
    ];
    
}
