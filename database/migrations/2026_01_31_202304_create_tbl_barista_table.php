<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblBaristaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_barista', function (Blueprint $table) {
            $table->id('barista_id');
            $table->string('barista_name', 150);
            $table->string('barista_email', 100)->unique();
            $table->string('barista_password');
            $table->string('barista_mpin')->nullable();
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
        Schema::dropIfExists('tbl_barista');
    }
}
