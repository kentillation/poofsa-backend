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
        Schema::table('tbl_products', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('user_id');
            $table->string('standard_image_path')->nullable()->after('thumbnail_path');
            $table->integer('image_size_kb')->nullable()->after('standard_image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_products', function (Blueprint $table) {
            //
        });
    }
};
