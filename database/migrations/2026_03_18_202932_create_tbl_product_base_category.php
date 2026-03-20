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
        Schema::create('tbl_product_base_category', function (Blueprint $table) {
            $table->id('product_base_category_id');
            $table->string('product_base_category');
            $table->string('category_subtitle_hiligaynon')->nullable();
            $table->string('category_subtitle_bisaya')->nullable();
            $table->string('category_subtitle_tagalog')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_product_base_category');
    }
};
