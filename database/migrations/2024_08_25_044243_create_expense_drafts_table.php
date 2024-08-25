<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpenseDraftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expense_drafts', function (Blueprint $table) {
            $table->uuid('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->smallInteger('type');
            $table->unsignedInteger('price');
            $table->date('date');
            $table->enum('status', ['pending', 'approved', 'denied'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->primary(['id', 'user_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expense_drafts');
    }
}
