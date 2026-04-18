<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiagnosticsTable extends Migration
{
    public function up()
    {
        Schema::create('diagnostics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_folder_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('diagnostics');
    }
}