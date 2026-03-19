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
            $table->string('shop_name', 150);
            $table->string('shop_owner', 150);
            $table->string('shop_address', 255);
            $table->string('shop_email', 100)->unique();
            $table->string('shop_contact_number', 13);
            $table->string('shop_type', 150);
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
