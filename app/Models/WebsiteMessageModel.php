<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteMessageModel extends Model
{
    use HasFactory;

    protected $table = 'tbl_website_message';

    protected $primaryKey = 'website_message_id';

    protected $guarded = [];

    protected $fillable = [
        'full_name', 'email', 'subject', 'message', 'created_at', 'updated_at'
    ];

    public $incrementing = true;

    protected $keyType = 'int';
}
