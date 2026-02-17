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
            $table->enum('order_type', ['DINE_IN', 'TAKEOUT', 'DELIVERY']);
            $table->enum('order_status', ['OPEN', 'PREPARING', 'SERVED', 'CANCELLED']);
            $table->string('table_number')->nullable();
            $table->string('order_note')->nullable();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
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
