<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePriceAndAmountColumnType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->change();
        });
        Schema::table('incomes', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });
        Schema::table('incomes', function (Blueprint $table) {
            $table->unsignedInteger('amount')->change();
        });
    }
}
