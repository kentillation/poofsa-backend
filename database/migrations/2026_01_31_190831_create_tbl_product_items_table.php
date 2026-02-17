<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProductItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product_items', function (Blueprint $table) {
            $table->id('product_item_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->decimal('quantity_required', 10, 3);
            $table->timestamps();
            $table->unique(['product_id', 'ingredient_id']);
            $table->foreign('product_id')->references('product_id')->on('tbl_products')->cascadeOnDelete();
            $table->foreign('ingredient_id')->references('ingredient_id')->on('tbl_ingredients')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_product_items');
    }
}
