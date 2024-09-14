<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatatableAttrJobCategoriesTable extends Migration

{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datatable_attr_job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('desc');
            $table->bigInteger('parent_id')->unsigned()->nullable();
            $table->integer('sort')->nullable();
            $table->timestamps();
        });

        Schema::table('datatable_attr_job_categories', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('datatable_attr_job_categories')->nullOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datatable_attr_job_categories');
    }
}
