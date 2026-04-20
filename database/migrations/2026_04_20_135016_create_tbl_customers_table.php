<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_customers', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('last_name', 50);
            $table->string('pet_name', 50)->nullable();
            $table->string('customer_contact_number', 13);
            $table->string('customer_email', 191)->unique();
            $table->string('customer_password');
            $table->string('customer_mpin')->nullable();
            $table->string('recovery_code')->nullable();
            $table->integer('recovery_attempts')->default(0);
            $table->timestamp('recovery_code_used_at')->nullable();
            $table->dateTime('recovery_code_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_customers');
    }
};
