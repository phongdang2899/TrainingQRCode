<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            // id of transaction
            $table->uuid('transaction_id');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            // id of code
            $table->unsignedBigInteger('code_id');
            $table->foreign('code_id')->references('id')->on('codes')->onDelete('cascade');
            $table->double('value');
            $table->tinyInteger('status')->nullable();
            $table->longText('transaction_info')->nullable();

            $table->primary(['transaction_id', 'code_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_items');
    }
}
