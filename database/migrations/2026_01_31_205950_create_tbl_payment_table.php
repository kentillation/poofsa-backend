<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTblPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_payment', function (Blueprint $table) {
            $table->id('payment_id');
            $table->text('payment_intent_id');
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('paymongo_payment_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('status', 20);
            $table->dateTime('paid_at')->nullable();
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
        Schema::dropIfExists('tbl_payment');
    }
}
