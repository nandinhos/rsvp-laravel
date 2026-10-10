<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #182738; font-size: 11px; margin: 0; }
        .header { background: #0B2A4A; color: #fff; padding: 20px 28px; }
        .header h1 { margin: 0 0 4px; font-size: 20px; }
        .header .meta { font-size: 11px; color: #cfe0f0; }
        .content { padding: 20px 28px; }
        .indicators { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .indicators td {
            border: 1px solid #d9e2ec; padding: 8px 10px; text-align: center; width: 16.6%;
        }
        .indicators .label { font-size: 9px; text-transform: uppercase; color: #627d98; }
        .indicators .value { font-size: 18px; font-weight: bold; color: #1C4E80; }
        table.guests { width: 100%; border-collapse: collapse; }
        table.guests th {
            background: #1C4E80; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px;
        }
        table.guests td { padding: 5px 8px; border-bottom: 1px solid #e3e9f0; }
        table.guests tr:nth-child(even) td { background: #F6F9FC; }
        .num { color: #627d98; font-family: DejaVu Sans Mono, monospace; }
        .totals { margin-top: 14px; font-size: 11px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 6px 28px;
            font-size: 9px; color: #829ab1; border-top: 1px solid #e3e9f0; }
        .footer .page:after { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $event->name }}</h1>
        <div class="meta">
            {{ $event->starts_at?->timezone($event->timezone)->format('d/m/Y H:i') }}
            · {{ $event->location }}
            · {{ $event->status->label() }}
        </div>
    </div>

    <div class="content">
        <table class="indicators">
            <tr>
                <td><div class="label">Confirmados</div><div class="value">{{ $metrics['confirmed_hosts'] }}</div></td>
                <td><div class="label">Acompanhantes</div><div class="value">{{ $metrics['confirmed_companions'] }}</div></td>
                <td><div class="label">Representantes</div><div class="value">{{ $metrics['confirmed_representatives'] }}</div></td>
                <td><div class="label">Não confirmados</div><div class="value">{{ $metrics['not_confirmed'] }}</div></td>
                <td><div class="label">Presenças</div><div class="value">{{ $metrics['present'] }}</div></td>
                <td><div class="label">Prev. / Estimado</div><div class="value">{{ $metrics['expected_participants'] }}/{{ $metrics['estimated_audience'] }}</div></td>
            </tr>
        </table>

        <table class="guests">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 34%;">Nome</th>
                    <th style="width: 20%;">Categoria</th>
                    <th style="width: 16%;">Papel</th>
                    <th style="width: 14%;">Confirmação</th>
                    <th style="width: 10%;">Presença</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($guests as $guest)
                    <tr>
                        <td class="num">{{ $guest->number }}</td>
                        <td>{{ $guest->full_name }}</td>
                        <td>{{ $guest->category?->name }}</td>
                        <td>{{ $guest->participation_role->label() }}</td>
                        <td>{{ $guest->rsvp_status->label() }}</td>
                        <td>{{ $guest->attendance_status->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <strong>Total listado:</strong> {{ $guests->count() }} convidado(s)
        </div>
    </div>

    <div class="footer">
        <span>{{ $event->name }} — Relatório de convidados</span>
        <span class="page" style="float: right;"></span>
    </div>
</body>
</html>
