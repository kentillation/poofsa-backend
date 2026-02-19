<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblStockMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_stock_movements', function (Blueprint $table) {
            $table->id('stock_movement_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('stock_batch_id')->nullable();
            $table->unsignedBigInteger('movement_type_id');
            $table->decimal('quantity', 10, 3);
            $table->string('reference_type')->nullable(); // sale, purchase, spoilage
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->index(['ingredient_id', 'stock_batch_id', 'movement_type_id']);
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
        Schema::dropIfExists('tbl_stock_movements');
    }
}
