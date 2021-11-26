<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveExpenseTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign('expenses_type_id_foreign');
            $table->dropIndex('expenses_type_id_foreign');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('type_id', 'type');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->smallInteger('type')->change();
        });
        Schema::dropIfExists('expense_types');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('type')->change();
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('type', 'type_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('type_id')->references('id')->on('expense_types');
        });
    }
}
