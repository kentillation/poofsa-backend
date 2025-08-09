<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductsHistoryModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_products_history';
    protected $guarded = [];
    protected $fillable = [
        'product_id', 'description', 'manage_id', 'shop_id', 'branch_id', 'user_id', 'created_at', 'updated_at',
    ];

    public function temperature()
    {
        return $this->belongsTo(TemperatureModel::class);
    }

    public function size()
    {
        return $this->belongsTo(SizeModel::class);
    }

    public function category()
    {
        return $this->belongsTo(CategoryModel::class);
    }

    public function availability()
    {
        return $this->belongsTo(AvailabilityModel::class);
    }

    public function visibility()
    {
        return $this->belongsTo(VisibilityModel::class);
    }
}
