<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Registra operações relevantes na auditoria (PRD §9, CA-10).
 * Append-only: nunca atualiza nem remove registros existentes.
 */
class AuditLogger
{
    public function log(
        string $action,
        ?User $user,
        ?Event $event = null,
        ?Model $auditable = null,
        array $changes = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'event_id' => $event?->id,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'action' => $action,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'changes' => $changes === [] ? null : $changes,
        ]);
    }
}
