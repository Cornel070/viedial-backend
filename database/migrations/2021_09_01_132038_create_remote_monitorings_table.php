<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemoteMonitoringsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('remote_monitorings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['blood_pressure', 'blood_sugar', 'weight', 'waist_line']);
            $table->integer('systolic')->nullable();
            $table->integer('diastolic')->nullable();
            $table->integer('blood_sugar_val')->nullable();
            $table->float('weight_val')->nullable();
            $table->float('waist_line_val')->nullable();
            $table->enum('timing', ['first_wake', 'before_meal', '2h_after_meal', 'bedtime'])->nullable();
            $table->string('level')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('remote_monitorings');
    }
}
