<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contador monotônico de numeração de convidados por evento (PRD §2.1, CA-13).
     * Garante numeração estável e NÃO reutilizada: incrementa sob lock atômico,
     * independente de exclusões lógicas e imune a corrida de concorrência.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('guest_number_seq')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('guest_number_seq');
        });
    }
};
