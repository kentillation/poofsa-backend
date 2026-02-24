<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifiedTypeModel extends Model
{
    protected $table = 'tbl_modified_type';

    protected $primaryKey = 'modified_type_id';

    protected $keyType = 'int';

    public $incrementing = true;
}
