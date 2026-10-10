<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AuditLog: histórico de operações relevantes (PRD §9, CA-10).
     * Protegido contra modificação por usuários comuns (append-only por convenção
     * de aplicação). Armazena autor, evento, registro afetado, ação, instante,
     * identificação técnica do dispositivo e alterações.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();

            // Registro afetado (polimórfico), quando aplicável.
            $table->nullableMorphs('auditable');

            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('changes')->nullable();

            // Instante da ação em UTC (apresentação no fuso do evento — PRD §9).
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
