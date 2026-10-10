<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * GuestRelationship: relações de acompanhamento e representação (PRD §2.4, §2.5).
     *
     * guest_id        = pessoa dependente (acompanhante OU representante)
     * related_guest_id = convidado titular ao qual está vinculada
     * type            = natureza ATUAL do vínculo (pode mudar na promoção, CA-05)
     * origin_type     = natureza ORIGINAL (preserva histórico: "era acompanhante")
     * is_conflict     = pendência bloqueante titular+representante confirmados (CA-06)
     */
    public function up(): void
    {
        Schema::create('guest_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete();
            $table->foreignId('related_guest_id')->constrained('guests')->cascadeOnDelete();

            $table->string('type');          // acompanhante | representante (atual)
            $table->string('origin_type');   // acompanhante | representante (original)

            // Conflito de resolução (CA-06): titular representado confirmou enquanto
            // havia representante ativo confirmado. Bloqueia consolidação até resolução.
            $table->boolean('is_conflict')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Uma pessoa tem no máximo um vínculo ativo com um titular.
            $table->unique(['guest_id', 'related_guest_id']);
            $table->index(['event_id', 'type']);
            $table->index('related_guest_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_relationships');
    }
};
