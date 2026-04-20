<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblShopBranchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_shop_branch', function (Blueprint $table) {
            $table->id('branch_id');
            $table->unsignedBigInteger('shop_id')->index();
            $table->string('branch_name');
            $table->string('branch_address');
            $table->string('branch_manager_name');
            $table->string('branch_contact_number', 13)->nullable();
            $table->decimal('branch_latitude', 10,7)->nullable();
            $table->decimal('branch_longitude', 11,7)->nullable();
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
        Schema::dropIfExists('tbl_shop_branch');
    }
}
