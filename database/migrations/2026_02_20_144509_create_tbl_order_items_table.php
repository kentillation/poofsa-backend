<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_order_items', function (Blueprint $table) {
            $table->id('order_item_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('shop_station_id');
            $table->unsignedBigInteger('station_status_id');
            $table->timestamps();
            $table->foreign('order_id')
                ->references('order_id')
                ->on('tbl_orders')
                ->cascadeOnDelete();
            $table->foreign('product_id')
                ->references('product_id')
                ->on('tbl_products')
                ->cascadeOnDelete();
            $table->foreign('variant_id')
                ->references('variant_id')
                ->on('tbl_product_variants')
                ->cascadeOnDelete();
            $table->foreign('shop_station_id')
                ->references('shop_station_id')
                ->on('tbl_shop_station')
                ->cascadeOnDelete();
            $table->foreign('station_status_id')
                ->references('station_status_id')
                ->on('tbl_station_status')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_order_items');
    }
};
