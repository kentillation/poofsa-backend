<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblAdminTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_admin', function (Blueprint $table) {
            $table->id('admin_id'); // admin ID
            $table->string('admin_name', 100); // admin full name
            $table->string('admin_email', 100)->unique(); // admin email
            $table->string('admin_password'); // hashed password
            $table->string('admin_mpin'); // mobile pin
            $table->unsignedBigInteger('shop_id'); // foreign key to shops table
            $table->enum('role', ['admin', 'superadmin', 'manager'])->default('admin'); // role: admin, superadmin, etc.
            $table->boolean('status')->default(true); // active/inactive status
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // optional: deleted_at for soft deletes
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_admin');
    }
}
