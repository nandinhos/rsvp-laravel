<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTimeTz('starts_at');
            $table->string('location');
            $table->string('cover_image_path')->nullable();
            $table->string('visibility')->default('reservado');
            $table->unsignedInteger('estimated_audience');
            $table->string('status')->default('rascunho');
            $table->string('timezone')->default('America/Sao_Paulo');
            // Política de retenção de dados pessoais (PRD §14.5), configurável, sem expurgo automático no MVP.
            $table->unsignedSmallInteger('retention_days')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
