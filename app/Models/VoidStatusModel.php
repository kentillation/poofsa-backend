<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoidStatusModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_void_status';

    protected $guarded = [];

    protected $primaryKey = 'void_status_id';

    protected $keyType = 'int';

    public function transactions()
    {
        return $this->hasMany(TransactionVoidModel::class, 'void_status_id', 'void_status_id');
    }
}
