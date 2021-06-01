<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVidCommentRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_comment_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('video_comment_id');
            $table->unsignedBigInteger('user_id');
            $table->string('reply_text');
            $table->timestamps();
            $table->foreign('video_comment_id')->references('id')->on('video_comments')->onDelete('cascade')->onUpdate('no action');
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
        Schema::dropIfExists('vid_comment_replies');
    }
}
