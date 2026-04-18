<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreatmentPlansTable extends Migration
{
    public function up()
    {
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_folder_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->text('description');
            $table->string('duration')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('treatment_plans');
    }
}