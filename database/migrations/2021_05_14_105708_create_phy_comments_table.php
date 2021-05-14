<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhyCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phy_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phy_category_id');
            $table->unsignedBigInteger('user_id');
            $table->string('comment_text');
            $table->timestamps();
            $table->foreign('phy_category_id')->references('id')->on('phy_categories')->onDelete('cascade')->onUpdate('no action');
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
        Schema::dropIfExists('phy_comments');
    }
}
