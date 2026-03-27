<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_shops', function (Blueprint $table) {
            $table->id('shop_id');
            $table->string('shop_name');
            $table->string('shop_owner');
            $table->string('shop_address');
            $table->string('shop_email')->unique();
            $table->string('shop_contact_number', 13);
            $table->string('shop_type');
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
        Schema::dropIfExists('tbl_shops');
    }
}
