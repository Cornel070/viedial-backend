<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appts', function (Blueprint $table) {
            $table->id();
            $table->date('appt_date');
            $table->string('appt_time');
            $table->string('reason');
            $table->integer('requestee_id');
            $table->string('requestee_name');
            $table->string('recipient_name');
            $table->integer('recipient_id');
            $table->string('status')->default('Pending');
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
        Schema::dropIfExists('appts');
    }
}
