<?php

namespace Database\Seeders;

use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\EventVisibility;
use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use App\Services\GuestNumberAllocator;
use App\Services\GuestRelationshipService;
use App\Services\RsvpService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuários de teste por perfil (senha padrão: password).
        $admin = $this->user('Administrador Demo', 'admin@rsvp.test');
        $gestor = $this->user('Gestor Demo', 'gestor@rsvp.test');
        $operador = $this->user('Operador Demo', 'operador@rsvp.test');
        $leitor = $this->user('Leitor Demo', 'leitor@rsvp.test');

        // Evento demonstrativo.
        $event = Event::create([
            'name' => 'Cerimônia Institucional 2026',
            'starts_at' => now()->addWeeks(3)->setTime(19, 0),
            'location' => 'Auditório Principal',
            'visibility' => EventVisibility::Reservado,
            'estimated_audience' => 200,
            'status' => EventStatus::Ativo,
            'timezone' => 'America/Sao_Paulo',
        ]);

        // Permissões por evento (um perfil por usuário neste evento demo).
        $event->users()->attach($admin, ['role' => EventRole::Administrador->value]);
        $event->users()->attach($gestor, ['role' => EventRole::Gestor->value]);
        $event->users()->attach($operador, ['role' => EventRole::Operador->value]);
        $event->users()->attach($leitor, ['role' => EventRole::Leitor->value]);

        // Categorias padrão.
        $categories = [];
        foreach (Event::DEFAULT_CATEGORIES as $i => $name) {
            $categories[$name] = GuestCategory::create([
                'event_id' => $event->id,
                'name' => $name,
                'sort_order' => $i,
            ]);
        }

        $allocator = app(GuestNumberAllocator::class);
        $links = app(GuestRelationshipService::class);
        $rsvp = app(RsvpService::class);

        $make = function (string $name, string $category, string $rsvpStatus = 'pendente') use ($event, $categories, $allocator) {
            return Guest::create([
                'event_id' => $event->id,
                'number' => $allocator->next($event),
                'full_name' => $name,
                'guest_category_id' => $categories[$category]->id,
                'rsvp_status' => $rsvpStatus,
            ]);
        };

        // Caso do PRD: Ana (titular, não confirmada) + Bruno (acompanhante que confirma => representante).
        $ana = $make('Ana Silva', 'Autoridade');
        $bruno = $make('Bruno Costa', 'Convidado geral');
        $links->attachCompanion($bruno, $ana);
        $rsvp->confirm($bruno); // Bruno promovido a representante (CA-05)

        // Titular confirmado com acompanhante confirmado (CA-04).
        $carlos = $make('Carlos Mendes', 'Diretoria', 'confirmado');
        $diana = $make('Diana Prince', 'Convidado geral');
        $links->attachCompanion($diana, $carlos);
        $rsvp->confirm($diana);

        // Alguns convidados variados.
        $make('Eduardo Lima', 'Comitê', 'confirmado');
        $make('Fernanda Rocha', 'Autoridade');
        $make('Gabriel Souza', 'Convidado geral', 'nao_participara');
        $make('Helena Dias', 'Diretoria', 'confirmado');

        // Múltiplos CONFLITOS de representação (CA-06) para validar caso a caso:
        // acompanhante confirma (vira representante) e, depois, o titular também
        // confirma — gerando confirmação mútua bloqueante.
        $conflictPairs = [
            ['Irene Fonseca', 'Autoridade', 'Jorge Antunes', 'Convidado geral'],
            ['Karina Belo', 'Diretoria', 'Lucas Prado', 'Convidado geral'],
            ['Marcos Viana', 'Comitê', 'Nadia Reis', 'Convidado geral'],
            ['Otávio Nunes', 'Autoridade', 'Paula Dantas', 'Convidado geral'],
        ];

        foreach ($conflictPairs as [$hostName, $hostCat, $repName, $repCat]) {
            $host = $make($hostName, $hostCat);
            $rep = $make($repName, $repCat);
            $links->attachCompanion($rep, $host);
            $rsvp->confirm($rep);  // acompanhante vira representante
            $rsvp->confirm($host); // titular confirma => conflito mútuo (CA-06)
        }
    }

    private function user(string $name, string $email): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
