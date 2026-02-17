<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class DevModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_dev';

    protected $primaryKey = 'dev_id';

    protected $keyType = 'int';

    protected $fillable = [
        'dev_name',
        'dev_email',
        'dev_password',
    ];

    protected $hidden = [
        'dev_password',
    ];

    protected $passwordField = 'dev_password';

    public function setPasswordAttribute($value)
    {
        $this->attributes['dev_password'] = $value;
    }

    public function getAuthPassword()
    {
        return $this->dev_password;
    }
    public function getAuthIdentifierName()
    {
        return 'dev_email';
    }
}
