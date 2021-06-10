<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->integer('current_weight');
            $table->integer('target_weight');
            $table->integer('calorie_balance');
            $table->integer('deficit_weight');
            $table->float('weekly_deficit');
            $table->float('weekly_calorie_def');
            $table->integer('length');
            $table->integer('calorie_burned_this_week')->default(0);
            $table->string('status')->default('in progress');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('no action');
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
        Schema::dropIfExists('goals');
    }
}
