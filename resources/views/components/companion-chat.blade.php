@props([
    'restaurant' => null,
    'open' => false,
    'embedded' => false,
])

@php
    $restaurantId = $restaurant?->id;
    $endpoint = route('sara.message');
    $historyUrl = route('sara.history');
    $resetUrl = route('companion.reset');
    $agentName = config('synoria.companion.name', 'Sara');
@endphp

<div
    x-data="companionChat({
        endpoint: @js($endpoint),
        historyUrl: @js($historyUrl),
        resetUrl: @js($resetUrl),
        restaurantId: @js($restaurantId),
        csrf: @js(csrf_token()),
        open: @js($open || $embedded),
        agentName: @js($agentName),
        embedded: @js($embedded),
    })"
    x-init="boot()"
    @class([
        'z-50' => ! $embedded,
        'fixed' => ! $embedded,
        'w-full max-w-xl mx-auto' => $embedded,
    ])
    @unless ($embedded)
        :style="panelStyle"
    @endunless
>
    @unless ($embedded)
        <button
            type="button"
            @click="openPanel()"
            class="inline-flex items-center gap-2 rounded-full bg-synoria-ink px-4 py-2.5 text-sm font-semibold text-white shadow-lg border border-synoria-yellow/40 hover:bg-synoria-ink/90 dark:bg-synoria-yellow dark:text-synoria-ink dark:border-synoria-yellow"
            x-show="!open"
            x-cloak
            x-transition
        >
            <span class="inline-flex h-2 w-2 rounded-full bg-synoria-yellow animate-pulse dark:bg-synoria-ink"></span>
            <span x-text="agentName"></span>
        </button>
    @endunless

    <div
        x-show="open"
        x-cloak
        @if (! $embedded) x-transition @endif
        @class([
            'flex flex-col overflow-hidden rounded-2xl border border-synoria-yellow/40 bg-white shadow-2xl dark:bg-slate-900 dark:border-slate-600',
            'h-[22rem] max-h-[70vh] w-[min(100vw-2rem,22rem)]' => ! $embedded,
            'h-[28rem] max-h-[75vh] w-full' => $embedded,
        ])
    >
        <div
            class="flex shrink-0 cursor-grab items-center justify-between gap-3 bg-synoria-ink px-3 py-2.5 text-white active:cursor-grabbing dark:bg-slate-800"
            @unless ($embedded)
                @mousedown.prevent="startDrag($event)"
            @endunless
        >
            <div class="min-w-0 select-none">
                <p class="truncate text-sm font-semibold text-synoria-yellow" x-text="agentName"></p>
                <p class="text-[11px] text-white/70">{{ __('Conseillère · SynoriaEats') }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-2" @mousedown.stop>
                <button type="button" class="text-xs text-white/70 hover:text-white" @click="resetChat()">{{ __('Effacer') }}</button>
                @unless ($embedded)
                    <button type="button" class="text-white/80 hover:text-white" @click="open = false" aria-label="{{ __('Fermer') }}">✕</button>
                @endunless
            </div>
        </div>

        <div
            class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-synoria-yellow-mist/30 px-3 py-2.5 dark:bg-slate-950/60"
            x-ref="scroller"
        >
            <div class="space-y-2.5">
                <template x-if="messages.length === 0 && !loadingHistory">
                    <p class="text-sm text-synoria-ink-soft whitespace-pre-line dark:text-slate-300" x-text="greeting"></p>
                </template>
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                        <div
                            class="inline-block max-w-[90%] break-words rounded-2xl px-3 py-2 text-sm leading-snug text-left prose-sara"
                            :class="msg.role === 'user'
                                ? 'bg-synoria-green text-white rounded-br-md whitespace-pre-wrap'
                                : 'bg-white text-synoria-ink border border-synoria-yellow/30 rounded-bl-md dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600'"
                            x-html="formatMessage(msg.content)"
                        ></div>
                    </div>
                </template>
                <div x-show="loading || loadingHistory" class="flex items-center gap-2 text-xs text-synoria-ink-faint dark:text-slate-400">
                    <span class="inline-flex gap-1">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-synoria-yellow [animation-delay:-0.2s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-synoria-yellow [animation-delay:-0.1s]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-synoria-yellow"></span>
                    </span>
                    <span x-text="loadingHistory ? '{{ __('Je retrouve nos échanges…') }}' : (agentName + ' {{ __('réfléchit…') }}')"></span>
                </div>
            </div>
        </div>

        <form class="flex shrink-0 gap-2 border-t border-synoria-yellow/25 p-2.5 dark:border-slate-700" @submit.prevent="send()">
            <input
                type="text"
                x-model="draft"
                placeholder="{{ __('Écris ici…') }}"
                class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder-slate-400"
                :disabled="loading"
            >
            <button
                type="submit"
                class="shrink-0 rounded-xl bg-synoria-yellow px-3 py-2 text-sm font-semibold text-synoria-ink hover:bg-synoria-yellow-deep disabled:opacity-60"
                :disabled="loading || !draft.trim()"
            >
                {{ __('Envoyer') }}
            </button>
        </form>
    </div>
</div>

@once
    @push('styles')
        <style>
            .prose-sara ul { margin: 0.35rem 0; padding-left: 1.1rem; list-style: disc; }
            .prose-sara li { margin: 0.2rem 0; }
            .prose-sara strong { font-weight: 600; }
            .prose-sara em { font-style: italic; opacity: 0.95; }
        </style>
    @endpush
    @push('scripts')
        <script>
            function companionChat(config) {
                return {
                    open: config.open || false,
                    embedded: config.embedded || false,
                    loading: false,
                    loadingHistory: false,
                    draft: '',
                    messages: [],
                    agentName: config.agentName || 'Sara',
                    greeting: '',
                    pos: { left: null, top: null },
                    dragging: false,
                    dragOffset: { x: 0, y: 0 },
                    get panelStyle() {
                        if (this.embedded) return {};
                        if (this.pos.left === null || this.pos.top === null) {
                            return { bottom: '1rem', left: '1rem', top: 'auto', right: 'auto' };
                        }
                        return {
                            left: this.pos.left + 'px',
                            top: this.pos.top + 'px',
                            bottom: 'auto',
                            right: 'auto',
                        };
                    },
                    async boot() {
                        this.greeting =
                            'Salut ! Je suis ' + this.agentName + ', ta conseillère SynoriaEats.\n' +
                            'Je me souviens de tes goûts et je te guide avec le vrai menu. Dis-moi ce que tu cherches.';
                        window.addEventListener('mousemove', (e) => this.onDrag(e));
                        window.addEventListener('mouseup', () => this.endDrag());
                        await this.loadHistory();
                    },
                    openPanel() {
                        this.open = true;
                        if (this.pos.left === null) {
                            this.pos = { left: 16, top: Math.max(80, window.innerHeight - 400) };
                        }
                    },
                    startDrag(e) {
                        if (this.embedded || !this.open) return;
                        const root = this.$el;
                        const rect = root.getBoundingClientRect();
                        if (this.pos.left === null) {
                            this.pos = { left: rect.left, top: rect.top };
                        }
                        this.dragging = true;
                        this.dragOffset = {
                            x: e.clientX - this.pos.left,
                            y: e.clientY - this.pos.top,
                        };
                    },
                    onDrag(e) {
                        if (!this.dragging) return;
                        const w = Math.min(window.innerWidth - 32, 352);
                        const h = 352;
                        this.pos.left = Math.min(Math.max(8, e.clientX - this.dragOffset.x), window.innerWidth - w - 8);
                        this.pos.top = Math.min(Math.max(8, e.clientY - this.dragOffset.y), window.innerHeight - h - 8);
                    },
                    endDrag() {
                        this.dragging = false;
                    },
                    async loadHistory() {
                        this.loadingHistory = true;
                        try {
                            const url = new URL(config.historyUrl, window.location.origin);
                            if (config.restaurantId) {
                                url.searchParams.set('restaurant_id', config.restaurantId);
                            }
                            const res = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (data.agent) this.agentName = data.agent;
                            if (Array.isArray(data.history)) this.messages = data.history;
                            this.$nextTick(() => this.scroll());
                        } catch (e) {
                        } finally {
                            this.loadingHistory = false;
                        }
                    },
                    async send(preset) {
                        const text = (preset || this.draft || '').trim();
                        if (!text || this.loading) return;
                        this.draft = '';
                        this.messages.push({ role: 'user', content: text });
                        this.loading = true;
                        this.$nextTick(() => this.scroll());
                        try {
                            const res = await fetch(config.endpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    message: text,
                                    restaurant_id: config.restaurantId,
                                }),
                            });
                            const data = await res.json();
                            if (!res.ok) throw new Error(data.message || 'Erreur agent');
                            if (data.agent) this.agentName = data.agent;
                            this.messages.push({ role: 'assistant', content: data.reply });
                        } catch (e) {
                            this.messages.push({
                                role: 'assistant',
                                content: 'Désolée, j’ai eu un blanc. Réessaie juste après.',
                            });
                        } finally {
                            this.loading = false;
                            this.$nextTick(() => this.scroll());
                        }
                    },
                    async resetChat() {
                        this.messages = [];
                        try {
                            await fetch(config.resetUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                        } catch (e) {}
                    },
                    scroll() {
                        const el = this.$refs.scroller;
                        if (el) el.scrollTop = el.scrollHeight;
                    },
                    formatMessage(text) {
                        let escaped = String(text || '')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;');
                        escaped = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
                        escaped = escaped.replace(/\*(.+?)\*/g, '<em>$1</em>');
                        // Listes Markdown (- item)
                        const lines = escaped.split(/\n/);
                        let html = '';
                        let inList = false;
                        for (const line of lines) {
                            const listMatch = line.match(/^\s*[-•]\s+(.+)$/);
                            if (listMatch) {
                                if (!inList) {
                                    html += '<ul>';
                                    inList = true;
                                }
                                html += '<li>' + listMatch[1] + '</li>';
                            } else {
                                if (inList) {
                                    html += '</ul>';
                                    inList = false;
                                }
                                html += line + '<br>';
                            }
                        }
                        if (inList) html += '</ul>';
                        return html.replace(/(<br>)+$/g, '');
                    },
                };
            }
        </script>
    @endpush
@endonce
