<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProductPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product_prices', function (Blueprint $table) {
            $table->id('price_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->timestamp('effective_from');
            $table->timestamp('effective_to')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('product_id')->references('product_id')->on('tbl_products')->cascadeOnDelete();
            $table->foreign('variant_id')->references('variant_id')->on('tbl_product_variants')->nullOnDelete();
            $table->index(['product_id', 'variant_id', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_product_prices');
    }
}
