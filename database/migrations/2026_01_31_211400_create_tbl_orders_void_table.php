<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblOrdersVoidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_orders_void', function (Blueprint $table) {
            $table->id('order_void_id');
            $table->unsignedBigInteger('order_id');
            $table->string('void_reason');
            $table->text('void_notes')->nullable();
            $table->unsignedBigInteger('voided_by');
            $table->timestamp('voided_at');
            $table->enum('void_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('from_quantity');
            $table->integer('to_quantity');
            $table->foreign('order_id')
                ->references('order_id')
                ->on('tbl_orders')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tbl_orders_void');
    }
}
