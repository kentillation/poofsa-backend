<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopHistoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_shop_history';

    protected $primaryKey = 'shop_history_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'shop_id',
        'description',
        'modified_type_id',
        'user_id',
    ];

    public function shops()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id');
    }

    public function modify()
    {
        return $this->belongsTo(ModifiedTypeModel::class, 'modified_type_id');
    }

    public function users()
    {
        return $this->belongsTo(AdminModel::class, 'user_id');
    }

}
