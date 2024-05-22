<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('belongs_to');
            $table->string('name');
            $table->string('type');
            $table->text('default_value')->nullable();
            $table->text('option_value')->nullable();
            $table->integer('grid_value');
            $table->boolean('is_table');
            $table->boolean('is_invoice');
            $table->boolean('is_required');
            $table->boolean('is_admin');
            $table->boolean('is_disable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('custom_fields');
    }
}
