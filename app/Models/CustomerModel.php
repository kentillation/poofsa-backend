<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class CustomerModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'tbl_customers';

    protected $primaryKey = 'customer_id';

    public $incrementing = true;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'pet_name',
        'customer_contact_number',
        'customer_email',
        'customer_password',
        'customer_mpin',
        'recovery_code',
        'recovery_attempts',
        'recovery_code_used_at',
        'recovery_code_expires_at',
        'is_active',
    ];

    // Overriding 'password'
    public function getAuthPassword()
    {
        return $this->customer_password;
    }
}
