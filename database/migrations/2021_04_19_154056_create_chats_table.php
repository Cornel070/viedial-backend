<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user1_id');
            $table->string('user1_name');
            $table->unsignedBigInteger('user2_id');
            $table->string('user2_name');
            $table->timestamps();
            $table->foreign('user1_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('no action');
            $table->foreign('user2_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('no action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
