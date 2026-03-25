<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_products', function (Blueprint $table) {
            $table->id('product_id');
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('size_id');
            $table->unsignedBigInteger('temp_id');
            $table->decimal('base_price', 10, 2); // current price only
            $table->decimal('cost_estimate', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('availability_id');
            $table->unsignedBigInteger('station_id');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->index('category_id');
            $table->index('size_id');
            $table->index('temp_id');
            $table->index('availability_id');
            $table->index('station_id');
            $table->index('shop_id');
            $table->index('branch_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_products');
    }
}
