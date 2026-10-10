<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guest: identificação e estados do convidado (PRD §2.1).
     * Numeração sequencial POR EVENTO, estável e não reutilizada (soft delete).
     * Categoria obrigatória (CA-02). RSVP, papel e presença são independentes.
     */
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Numeração exclusiva no evento (PRD §2.1). Atribuída de forma monotônica
            // via tabela de sequência; estável mesmo após exclusão lógica.
            $table->unsignedInteger('number');

            $table->string('full_name');

            // Categoria obrigatória (CA-02). Restrição NOT NULL; integridade reforçada por FK.
            $table->foreignId('guest_category_id')->constrained('guest_categories')->restrictOnDelete();

            // Estados independentes.
            $table->string('rsvp_status')->default('pendente');
            $table->string('participation_role')->default('titular');
            $table->string('attendance_status')->default('aguardando');

            // Credenciamento (PRD §2.6): horário, responsável.
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Numeração única por evento, inclusive considerando registros excluídos
            // logicamente — a sequência (events.guest_number_seq) garante não reutilização.
            $table->unique(['event_id', 'number']);
            $table->index(['event_id', 'rsvp_status']);
            $table->index(['event_id', 'participation_role']);
            $table->index(['event_id', 'attendance_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
