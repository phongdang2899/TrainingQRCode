<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watchers', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip');
            $table->smallInteger('times')->unsigned();
            $table->dateTime('previous_time')->nullable();
            $table->smallInteger('total_times')->unsigned();
            $table->string('phone_number')->nullable();
            $table->string('token')->nullable();

            $table->timestamps();

            $table->index(['ip']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('watchers');
    }
}
