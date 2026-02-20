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
            $table->integer('total_quantity');
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
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
