<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblProductCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_product_category', function (Blueprint $table) {
            $table->id('product_category_id');
            $table->string('category_label', 100);
            $table->unsignedBigInteger('shop_id');
            $table->boolean('is_active')->default(true); // active/inactive status
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
        Schema::dropIfExists('tbl_product_category');
    }
}
