@props([
    'order',
    'poll' => false,
])

@php
    $events = $order->relationLoaded('statusEvents')
        ? $order->statusEvents
        : $order->statusEvents()->with('actor')->get();
@endphp

<div class="synoria-panel sm:rounded-2xl p-6" data-order-timeline @if ($poll) data-tracking-url="{{ route('orders.tracking', $order) }}" @endif>
    <h3 class="font-semibold text-synoria-ink mb-4">Suivi de préparation</h3>

    <ol class="space-y-3" data-timeline-list>
        @forelse ($events as $event)
            <li class="flex gap-3">
                <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $event->to_status === $order->status ? 'bg-synoria-green' : 'bg-synoria-yellow' }}"></span>
                <div>
                    <p class="text-sm font-medium text-synoria-ink">{{ $event->to_status->label() }}</p>
                    <p class="text-xs text-synoria-ink-soft">
                        {{ $event->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                        @if ($event->actor)
                            · {{ $event->actor->name }}
                        @endif
                    </p>
                    @if ($event->note)
                        <p class="text-xs text-synoria-ink-faint mt-0.5">{{ $event->note }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li class="text-sm text-synoria-ink-soft">Aucun historique pour l’instant.</li>
        @endforelse
    </ol>
</div>

@if ($poll)
    <script>
        (function () {
            const root = document.querySelector('[data-order-timeline][data-tracking-url]');
            if (!root) return;
            const list = root.querySelector('[data-timeline-list]');
            const url = root.dataset.trackingUrl;
            const poll = async () => {
                const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                }[char]));
                try {
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    if (!Array.isArray(data.timeline) || !list) return;
                    list.innerHTML = data.timeline.map((event) => `
                        <li class="flex gap-3">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full ${event.current ? 'bg-synoria-green' : 'bg-synoria-yellow'}"></span>
                            <div>
                                <p class="text-sm font-medium text-synoria-ink">${esc(event.to_status_label)}</p>
                                <p class="text-xs text-synoria-ink-soft">${esc(event.at)}${event.actor ? ' · ' + esc(event.actor) : ''}</p>
                                ${event.note ? `<p class="text-xs text-synoria-ink-faint mt-0.5">${esc(event.note)}</p>` : ''}
                            </div>
                        </li>
                    `).join('') || '<li class="text-sm text-synoria-ink-soft">Aucun historique pour l’instant.</li>';
                    const label = document.getElementById('status-label');
                    if (label && data.status_label) label.textContent = data.status_label;
                    if (['delivered', 'cancelled'].includes(data.status)) {
                        clearInterval(timer);
                    }
                } catch (e) {}
            };
            const timer = setInterval(poll, 4000);
            poll();
        })();
    </script>
@endif
