<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblKitchenPersonnelTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_kitchen_personnel', function (Blueprint $table) {
            $table->id('kitchen_personnel_id');
            $table->string('kitchen_personnel_name', 150);
            $table->string('kitchen_personnel_email', 100)->unique();
            $table->string('kitchen_personnel_password');
            $table->string('kitchen_personnel_mpin')->nullable();
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
        Schema::dropIfExists('tbl_kitchen_personnel');
    }
}
