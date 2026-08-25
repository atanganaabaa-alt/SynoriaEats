@props([
    'restaurant' => null,
    'open' => false,
    'embedded' => false,
])

@php
    $restaurantId = $restaurant?->id;
    $endpoint = route('companion.message');
    $historyUrl = route('companion.history');
    $resetUrl = route('companion.reset');
    $agentName = config('synoria.companion.name', 'Amina');
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
        'fixed bottom-4 left-4 w-[min(100vw-2rem,20rem)]' => ! $embedded,
        'w-full max-w-xl mx-auto' => $embedded,
    ])
>
    @unless ($embedded)
        <button
            type="button"
            @click="open = !open"
            class="inline-flex items-center gap-2 rounded-full bg-synoria-ink px-4 py-2.5 text-sm font-semibold text-white shadow-lg border border-synoria-yellow/40 hover:bg-synoria-ink/90"
            x-show="!open"
            x-cloak
        >
            <span class="inline-flex h-2 w-2 rounded-full bg-synoria-yellow animate-pulse"></span>
            <span x-text="agentName"></span>
        </button>
    @endunless

    <div
        x-show="open"
        x-cloak
        @if (! $embedded) x-transition @endif
        class="flex h-[22rem] max-h-[55vh] w-full flex-col overflow-hidden rounded-2xl border border-synoria-yellow/40 bg-white shadow-2xl"
    >
        <div class="flex shrink-0 items-center justify-between gap-3 bg-synoria-ink px-3 py-2.5 text-white">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-synoria-yellow" x-text="agentName"></p>
                <p class="text-[11px] text-white/70">Conseillère · SynoriaEats</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" class="text-xs text-white/70 hover:text-white" @click="resetChat()">Effacer</button>
                @unless ($embedded)
                    <button type="button" class="text-white/80 hover:text-white" @click="open = false" aria-label="Fermer">✕</button>
                @endunless
            </div>
        </div>

        <div
            class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-synoria-yellow-mist/30 px-3 py-2.5"
            style="max-height: 12rem;"
            x-ref="scroller"
        >
            <div class="space-y-2.5">
                <template x-if="messages.length === 0 && !loadingHistory">
                    <p class="text-sm text-synoria-ink-soft" x-text="greeting"></p>
                </template>
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                        <div
                            class="inline-block max-w-[85%] whitespace-pre-wrap break-words rounded-2xl px-3 py-2 text-sm leading-snug"
                            :class="msg.role === 'user'
                                ? 'bg-synoria-green text-white rounded-br-md'
                                : 'bg-white text-synoria-ink border border-synoria-yellow/30 rounded-bl-md'"
                            x-text="msg.content"
                        ></div>
                    </div>
                </template>
                <p x-show="loading || loadingHistory" class="text-xs text-synoria-ink-faint" x-text="loadingHistory ? 'Je retrouve nos échanges…' : (agentName + ' réfléchit…')"></p>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap gap-1.5 border-t border-synoria-yellow/20 px-3 py-1.5 max-h-14 overflow-y-auto" x-show="suggestions.length">
            <template x-for="chip in suggestions" :key="chip">
                <button
                    type="button"
                    class="rounded-full bg-synoria-yellow/20 px-2 py-0.5 text-[11px] font-medium text-synoria-ink hover:bg-synoria-yellow/35"
                    @click="send(chip)"
                    x-text="chip"
                ></button>
            </template>
        </div>

        <form class="flex shrink-0 gap-2 border-t border-synoria-yellow/25 p-2.5" @submit.prevent="send()">
            <input
                type="text"
                x-model="draft"
                placeholder="Écris ici…"
                class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                :disabled="loading"
            >
            <button
                type="submit"
                class="shrink-0 rounded-xl bg-synoria-yellow px-3 py-2 text-sm font-semibold text-synoria-ink hover:bg-synoria-yellow-deep disabled:opacity-60"
                :disabled="loading || !draft.trim()"
            >
                Envoyer
            </button>
        </form>
    </div>
</div>

@once
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
                    agentName: config.agentName || 'Amina',
                    greeting: '',
                    suggestions: ['J’ai faim, guide-moi', 'J’ai environ 5000 FCFA', 'Quelque chose de local'],
                    async boot() {
                        this.greeting = 'Salut ! Moi c’est ' + this.agentName + '. Budget ou envie, on trouve ensemble.';
                        await this.loadHistory();
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
                            if (Array.isArray(data.suggestions) && data.suggestions.length) {
                                this.suggestions = data.suggestions;
                            }
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
                            if (Array.isArray(data.suggestions) && data.suggestions.length) {
                                this.suggestions = data.suggestions;
                            }
                        } catch (e) {
                            this.messages.push({
                                role: 'assistant',
                                content: 'Désolée, j’ai eu un blanc. Réessaie juste après. Je reste avec toi.',
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
                };
            }
        </script>
    @endpush
@endonce
