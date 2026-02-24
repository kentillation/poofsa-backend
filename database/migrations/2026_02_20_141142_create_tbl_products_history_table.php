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
        Schema::create('tbl_products_history', function (Blueprint $table) {
            $table->id('product_history_id');
            $table->unsignedBigInteger('product_id');
            $table->longText('description');
            $table->unsignedBigInteger('modified_type_id')->index();
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
        Schema::dropIfExists('tbl_products_history');
    }
};
