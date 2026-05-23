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
            $table->string('shop_name', 50);
            $table->string('shop_type', 50);
            $table->string('shop_owner', 50);
            $table->string('shop_email', 191)->unique();
            $table->string('shop_contact_number', 13);
            $table->boolean('is_active')->default(true); // active/inactive status
            $table->boolean('is_overnight')->default(false);
            $table->time('open_at');
            $table->time('close_at');
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
