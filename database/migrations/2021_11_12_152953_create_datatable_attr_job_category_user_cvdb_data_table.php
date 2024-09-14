<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatatableAttrJobCategoryUserCvdbDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datatable_attr_job_category_datatable_user', function (Blueprint $table) {

            $table->unsignedBigInteger('datatable_user_id')->nullable();
            $table->unsignedBigInteger('datatable_attr_job_category_id')->nullable();

            $table->foreign('datatable_user_id', 'fk_attr_job_category_user_id')->references('id')->on('datatable_users')->nullOnDelete();
            $table->foreign('datatable_attr_job_category_id', 'fk_user_id_attr_job_category_id')->references('id')->on('datatable_attr_job_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datatable_attr_job_category_datatable_user');
    }
}
