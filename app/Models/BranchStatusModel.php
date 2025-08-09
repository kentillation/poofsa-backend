<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchStatusModel extends Model
{
    use HasFactory;
    protected $table = 'tbl_shop_branch_status';
    protected $guarded = [];
}
