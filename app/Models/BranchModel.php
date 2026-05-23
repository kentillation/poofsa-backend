<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_shop_branch';

    protected $primaryKey = 'branch_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'shop_id',
        'branch_name',
        'branch_address',
        'branch_manager_name',
        'branch_contact_number',
        'branch_latitude',
        'branch_longitude',
        'open_at',
        'close_at',
        'is_overnight',
        'is_active',
    ];

    // Mutator for open_at - ensures proper format for database storage
    public function setOpenAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['open_at'] = null;
            return;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            $this->attributes['open_at'] = $value . ':00';
        } else {
            $this->attributes['open_at'] = $value;
        }
    }

    // Mutator for close_at - ensures proper format for database storage
    public function setCloseAtAttribute($value)
    {
        if (!$value) {
            $this->attributes['close_at'] = null;
            return;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            $this->attributes['close_at'] = $value . ':00';
        } else {
            $this->attributes['close_at'] = $value;
        }
    }

    public function shop()
    {
        return $this->belongsTo(ShopModel::class, 'shop_id', 'shop_id');
    }

	public function products()
	{
	    return $this->hasMany(ProductsModel::class, 'branch_id', 'branch_id');
	}
}
