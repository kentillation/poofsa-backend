<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblSaleItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_sale_items', function (Blueprint $table) {
            $table->id('sale_item_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->timestamps();
            $table->foreign('sale_id')->references('sale_id')->on('tbl_sales')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('tbl_products');
            $table->foreign('variant_id')->references('variant_id')->on('tbl_product_variants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_sale_items');
    }
}
