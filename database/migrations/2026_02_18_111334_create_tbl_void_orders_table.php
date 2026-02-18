<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblVoidOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_void_orders', function (Blueprint $table) {
            $table->id('void_order_id');
            $table->unsignedBigInteger('order_id');
            $table->integer('product_id');
            $table->string('reference_number');
            $table->string('void_reason');
            $table->text('void_notes')->nullable();
            $table->unsignedBigInteger('voided_by');
            $table->timestamp('voided_at');
            $table->integer('void_status_id');
            $table->integer('from_quantity');
            $table->integer('to_quantity');
            $table->integer('shop_id');
            $table->integer('branch_id');
            $table->foreign('order_id')
                ->references('order_id')
                ->on('tbl_orders')
                ->cascadeOnDelete();
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
        Schema::dropIfExists('tbl_void_orders');
    }
}
