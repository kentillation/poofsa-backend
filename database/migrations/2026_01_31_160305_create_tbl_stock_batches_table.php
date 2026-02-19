<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblStockBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_stock_batches', function (Blueprint $table) {
            $table->id('stock_batch_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->string('batch_code', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('quantity_received', 10, 3);
            $table->decimal('quantity_remaining', 10, 3);
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();
            $table->index(['ingredient_id', 'expiry_date']);
            $table->index(['shop_id', 'branch_id']);
            $table->foreign('ingredient_id')
                ->references('ingredient_id')
                ->on('tbl_ingredients')
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
        Schema::dropIfExists('tbl_stock_batches');
    }
}
