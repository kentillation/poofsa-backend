<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class ShopModel extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'tbl_shops';

    protected $primaryKey = 'shop_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'shop_name',
        'shop_type',
        'shop_owner',
        'shop_email',
        'shop_contact_number',
        'shop_accent_color',
        'thumbnail_path',
        'standard_image_path',
        'image_size_kb',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accessors for URLs
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        // Local
        return Storage::disk('public')->exists($this->thumbnail_path)
            ? asset('storage/' . $this->thumbnail_path)
            : null;

        // Production
        // return Storage::disk('public')->exists($this->thumbnail_path)
        //     ? asset('storage/app/public/' . $this->thumbnail_path)
        //     : null;
    }

    public function getStandardImageUrlAttribute()
    {
        if (!$this->standard_image_path) {
            return null;
        }

        // Local
        return Storage::disk('public')->exists($this->standard_image_path)
            ? asset('storage/' . $this->standard_image_path)
            : null;

        // Production
        // return Storage::disk('public')->exists($this->standard_image_path)
        //     ? asset('storage/app/public/' . $this->standard_image_path)
        //     : null;
    }

    // Accessor to check if shop has image
    public function getHasImageAttribute()
    {
        // Local
        return !is_null($this->thumbnail_path) &&
            Storage::disk('public')->exists($this->thumbnail_path);

        // Production
        // return !is_null($this->thumbnail_path) &&
        //     Storage::disk('public')->exists($this->thumbnail_path)
		// 	? asset('storage/app/public/' . $this->thumbnail_path)
        //     : null;
    }

    public function branches()
    {
        return $this->hasMany(BranchModel::class, 'shop_id', 'shop_id');
    }

    public function products()
    {
        return $this->hasMany(ProductsModel::class, 'shop_id');
    }

    public function lowestPricedProduct()
    {
        return $this->hasOne(ProductsModel::class, 'shop_id')
            ->where('availability_id', 1)
            ->orderBy('base_price', 'asc')
            ->orderBy('product_id', 'asc');
    }
}
