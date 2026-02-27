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
        Schema::create('tbl_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_number')->unique();
            $table->string('reference_number')->unique();
            $table->decimal('customer_cash', 10, 2);
            $table->decimal('customer_change', 10, 2);
            $table->unsignedBigInteger('order_type_id')->index();
            $table->unsignedBigInteger('order_status_id')->index();
            $table->string('table_number')->nullable();
            $table->string('order_note')->nullable();
            $table->unsignedBigInteger('total_quantity');
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->foreign('order_type_id')
                ->references('order_type_id')
                ->on('tbl_order_type')
                ->cascadeOnDelete();
            $table->foreign('order_status_id')
                ->references('order_status_id')
                ->on('tbl_order_status')
                ->cascadeOnDelete();
            $table->foreign('shop_id')
                ->references('shop_id')
                ->on('tbl_shops')
                ->cascadeOnDelete();
            $table->foreign('branch_id')
                ->references('branch_id')
                ->on('tbl_shop_branch')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('admin_id')
                ->on('tbl_admin')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_orders');
    }
};
