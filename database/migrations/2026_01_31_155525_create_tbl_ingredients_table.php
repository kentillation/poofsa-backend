<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblIngredientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_ingredients', function (Blueprint $table) {
            $table->id('ingredient_id');
            $table->string('ingredient_name');
            $table->unsignedBigInteger('base_unit_id'); // grams, ml, pcs
            $table->decimal('alert_quantity', 10, 3);
            $table->integer('availability_id', 12)->index();
            $table->integer('shop_id', 12)->index();
            $table->integer('branch_id', 12)->index();
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
        Schema::dropIfExists('tbl_ingredients');
    }
}
