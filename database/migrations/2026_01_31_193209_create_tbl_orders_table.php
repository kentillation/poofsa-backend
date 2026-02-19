<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_number')->unique();
            $table->string('reference_number')->unique();
            $table->decimal('customer_cash', 10, 2);
            $table->decimal('customer_change', 10, 2);
            $table->enum('order_type', ['DINE_IN', 'TAKEOUT', 'DELIVERY']);
            $table->enum('order_status', ['OPEN', 'PREPARING', 'SERVED', 'CANCELLED']);
            $table->string('table_number')->nullable();
            $table->string('order_note')->nullable();
            $table->integer('total_quantity')();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('user_id')->index();
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
        Schema::dropIfExists('tbl_orders');
    }
}
