<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'authorizedEvents' => fn () => $request->user()
                ? $request->user()->events()
                    ->orderByDesc('starts_at')
                    ->get(['events.id', 'events.name', 'events.status'])
                    ->map(fn ($event) => [
                        'id' => $event->id,
                        'name' => $event->name,
                        'status' => $event->status->value,
                    ])
                : [],
            'activeEvent' => function () use ($request) {
                $event = $request->route('event');

                return $event instanceof \App\Models\Event
                    ? ['id' => $event->id, 'name' => $event->name, 'status' => $event->status->value]
                    : null;
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'importPreview' => fn () => $request->session()->get('importPreview'),
            ],
        ];
    }
}
