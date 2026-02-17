<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProductVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product_variants', function (Blueprint $table) {
            $table->id('variant_id');
            $table->unsignedBigInteger('product_id');
            $table->string('variant_name');
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->string('sku')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('product_id')->references('product_id')->on('tbl_products')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_product_variants');
    }
}
