<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshTokenModel extends Model
{
    use HasFactory;

    protected $table = 'refresh_tokens';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
    ];
}
