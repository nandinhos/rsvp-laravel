<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de contatos com o convidado (M1 — confirmação ativa).
 * Append-only: cada tentativa é um registro; nunca sobrescreve anteriores.
 * Isolado por evento (CA-01).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('channel');   // ContactChannel
            $table->string('outcome');   // ContactOutcome
            $table->timestamp('scheduled_return_at')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'guest_id']);
            $table->index(['event_id', 'outcome']);
            $table->index(['event_id', 'scheduled_return_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_contacts');
    }
};
