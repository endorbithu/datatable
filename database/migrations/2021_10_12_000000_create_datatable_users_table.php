<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatatableUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datatable_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->boolean('is_newsletter')->nullable();
            $table->date('birthday')->nullable();
            $table->integer('net_wage_demand')->nullable();
            $table->unsignedBigInteger('datatable_attr_school_degree_id')->nullable();
            $table->timestamps();

            $table->foreign('datatable_attr_school_degree_id')->references('id')->on('datatable_attr_school_degrees')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('datatable_users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('datatable_attr_school_degree_id');
        });

        Schema::dropIfExists('datatable_users');
    }
}
