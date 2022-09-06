<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_history', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->integer('type_id');
            $table->decimal('amount');
            $table->string('info')->nullable();
            $table->integer('transaction_id');   // order id or transation id
            $table->string('verifyToken');   // order id or transation id
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
        Schema::dropIfExists('wallet_history');
    }
}
