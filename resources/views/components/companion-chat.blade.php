@props([
    'restaurant' => null,
    'open' => false,
])

@php
    $restaurantId = $restaurant?->id;
    $endpoint = route('companion.message');
    $resetUrl = route('companion.reset');
@endphp

<div
    x-data="companionChat({
        endpoint: @js($endpoint),
        resetUrl: @js($resetUrl),
        restaurantId: @js($restaurantId),
        csrf: @js(csrf_token()),
        open: @js($open),
    })"
    class="fixed bottom-4 left-4 z-50 w-[min(100vw-2rem,22rem)]"
>
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center gap-2 rounded-full bg-synoria-ink px-4 py-3 text-sm font-semibold text-white shadow-lg border border-synoria-yellow/40 hover:bg-synoria-ink/90"
        x-show="!open"
        x-cloak
    >
        <span class="inline-flex h-2 w-2 rounded-full bg-synoria-yellow"></span>
        Compagnon
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="overflow-hidden rounded-2xl border border-synoria-yellow/40 bg-white shadow-2xl"
    >
        <div class="flex items-center justify-between gap-3 bg-synoria-ink px-4 py-3 text-white">
            <div>
                <p class="text-sm font-semibold text-synoria-yellow">Compagnon SynoriaEats</p>
                <p class="text-xs text-white/70">Menu · budget · attente</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="text-xs text-white/70 hover:text-white" @click="resetChat()">Effacer</button>
                <button type="button" class="text-white/80 hover:text-white" @click="open = false" aria-label="Fermer">✕</button>
            </div>
        </div>

        <div class="max-h-72 space-y-3 overflow-y-auto bg-synoria-yellow-mist/30 px-4 py-3" x-ref="scroller">
            <template x-if="messages.length === 0">
                <p class="text-sm text-synoria-ink-soft">
                    Demande une idée de plat, un budget (ex. 5000 FCFA), ou le temps d’attente.
                </p>
            </template>
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'text-right' : 'text-left'">
                    <div
                        class="inline-block max-w-[90%] whitespace-pre-wrap rounded-2xl px-3 py-2 text-sm"
                        :class="msg.role === 'user'
                            ? 'bg-synoria-green text-white rounded-br-md'
                            : 'bg-white text-synoria-ink border border-synoria-yellow/30 rounded-bl-md'"
                        x-text="msg.content"
                    ></div>
                </div>
            </template>
            <p x-show="loading" class="text-xs text-synoria-ink-faint">Le compagnon réfléchit…</p>
        </div>

        <div class="flex flex-wrap gap-2 border-t border-synoria-yellow/20 px-3 py-2" x-show="suggestions.length">
            <template x-for="chip in suggestions" :key="chip">
                <button
                    type="button"
                    class="rounded-full bg-synoria-yellow/20 px-2.5 py-1 text-[11px] font-medium text-synoria-ink hover:bg-synoria-yellow/35"
                    @click="send(chip)"
                    x-text="chip"
                ></button>
            </template>
        </div>

        <form class="flex gap-2 border-t border-synoria-yellow/25 p-3" @submit.prevent="send()">
            <input
                type="text"
                x-model="draft"
                placeholder="Écris ton message…"
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
                    loading: false,
                    draft: '',
                    messages: [],
                    suggestions: ['J’ai 5000 FCFA', 'Que me recommandes-tu ?', 'Combien de temps d’attente ?'],
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
                            if (!res.ok) throw new Error(data.message || 'Erreur compagnon');
                            this.messages.push({ role: 'assistant', content: data.reply });
                            if (Array.isArray(data.suggestions) && data.suggestions.length) {
                                this.suggestions = data.suggestions;
                            }
                        } catch (e) {
                            this.messages.push({
                                role: 'assistant',
                                content: 'Désolé, je n’ai pas pu répondre. Réessaie dans un instant.',
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
