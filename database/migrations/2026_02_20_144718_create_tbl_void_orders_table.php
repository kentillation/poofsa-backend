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
        Schema::create('tbl_void_orders', function (Blueprint $table) {
            $table->id('void_order_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('reference_number');
            $table->string('void_reason');
            $table->text('void_notes')->nullable();
            $table->unsignedBigInteger('voided_by');
            $table->timestamp('voided_at');
            $table->unsignedBigInteger('void_status_id');
            $table->integer('from_quantity');
            $table->integer('to_quantity');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();
            $table->foreign('order_id')
                ->references('order_id')
                ->on('tbl_orders')
                ->cascadeOnDelete();
            $table->foreign('product_id')
                ->references('product_id')
                ->on('tbl_products')
                ->cascadeOnDelete();
            $table->foreign('voided_by')
                ->references('cashier_id')
                ->on('tbl_cashier')
                ->cascadeOnDelete();
            $table->foreign('void_status_id')
                ->references('void_status_id')
                ->on('tbl_void_status')
                ->cascadeOnDelete();
            $table->foreign('shop_id')
                ->references('shop_id')
                ->on('tbl_shops')
                ->cascadeOnDelete();
            $table->foreign('branch_id')
                ->references('branch_id')
                ->on('tbl_shop_branch')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_void_orders');
    }
};
