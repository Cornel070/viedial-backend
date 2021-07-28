<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->date('dob');
            $table->string('phone');
            $table->string('password');
            $table->string('annon_name');
            $table->string('program')->nullable();
            $table->string('gender');
            $table->string('acct_key')->nullable();
            $table->string('status')->default("inactive");//Subscription status
            $table->string('role')->default('Client');
            $table->string('device_id');
            $table->string('sub_type')->nullable();
            $table->timestamp('email_verified_at')->nullable();
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
        Schema::dropIfExists('users');
    }
}
