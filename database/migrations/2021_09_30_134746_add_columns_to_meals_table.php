<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMealsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->string('calories');
            $table->string('carbs');
            $table->string('fibre');
            $table->string('protein');
            $table->string('fat');
            $table->string('potassium');
            $table->string('sodium');
            $table->string('carb_serving');
            $table->string('sodium_potash');
            $table->string('others');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn('calories');
            $table->dropColumn('carbs');
            $table->dropColumn('fibre');
            $table->dropColumn('protein');
            $table->dropColumn('fat');
            $table->dropColumn('potassium');
            $table->dropColumn('sodium');
            $table->dropColumn('carb_serving');
            $table->dropColumn('sodium_potash');
            $table->dropColumn('others');
        });
    }
}
