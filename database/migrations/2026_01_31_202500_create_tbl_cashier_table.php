<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblCashierTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_cashier', function (Blueprint $table) {
            $table->id('cashier_id');
            $table->string('cashier_name', 150);
            $table->string('cashier_email', 100)->unique();
            $table->string('cashier_password');
            $table->string('cashier_mpin');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('branch_id');
            $table->boolean('is_active')->default(true); // active/inactive status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_cashier');
    }
}
