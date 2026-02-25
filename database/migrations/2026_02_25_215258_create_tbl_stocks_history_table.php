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
        Schema::create('tbl_stocks_history', function (Blueprint $table) {
            $table->id('stock_history_id');
            $table->unsignedBigInteger('ingredient_id')->index();
            $table->text('description');
            $table->unsignedBigInteger('modified_type_id')->index();
            $table->unsignedBigInteger('shop_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();
            $table->foreign('ingredient_id')
                ->references('ingredient_id')
                ->on('tbl_ingredients')
                ->cascadeOnDelete();
            $table->foreign('modified_type_id')
                ->references('modified_type_id')
                ->on('tbl_modified_type')
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
        Schema::dropIfExists('tbl_stocks_history');
    }
};
