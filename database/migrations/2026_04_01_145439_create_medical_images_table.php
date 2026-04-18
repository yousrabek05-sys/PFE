<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicalImagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_folder_id')->constrained()->onDelete('cascade');
            $table->string('type'); // X-ray, intra-oral, etc.
            $table->string('path'); // File path
            $table->text('description')->nullable();
            $table->text('ai_analysis')->nullable(); // AI analysis results
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_images');
    }
}
