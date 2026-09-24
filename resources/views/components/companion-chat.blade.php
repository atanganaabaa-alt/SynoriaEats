@props([
    'restaurant' => null,
    'open' => false,
    'embedded' => false,
    'threads' => false,
    'initialConversationId' => null,
])

@php
    $restaurantId = $restaurant?->id;
    $endpoint = route('sara.message');
    $historyUrl = route('sara.history');
    $resetUrl = route('sara.reset');
    $conversationsUrl = route('sara.conversations');
    $storeConversationUrl = route('sara.conversations.store');
    $agentName = config('synoria.companion.name', 'Sara');
@endphp

<div
    x-data="companionChat({
        endpoint: @js($endpoint),
        historyUrl: @js($historyUrl),
        resetUrl: @js($resetUrl),
        conversationsUrl: @js($conversationsUrl),
        storeConversationUrl: @js($storeConversationUrl),
        destroyConversationUrlBase: @js(url('/api/sara/conversations')),
        restaurantId: @js($restaurantId),
        csrf: @js(csrf_token()),
        open: @js($open || $embedded),
        agentName: @js($agentName),
        embedded: @js($embedded),
        threads: @js($threads),
        conversationId: @js($initialConversationId),
    })"
    x-init="boot()"
    @class([
        'z-50' => ! $embedded,
        'fixed' => ! $embedded,
        'w-full' => $embedded,
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
            'flex overflow-hidden rounded-2xl border border-synoria-yellow/40 bg-white shadow-2xl dark:bg-slate-900 dark:border-slate-600',
            'h-[min(32rem,78vh)] w-[min(100vw-1.5rem,24rem)] flex-col' => ! $embedded,
            'h-[min(70vh,36rem)] w-full flex-row' => $embedded && $threads,
            'h-[min(70vh,36rem)] w-full flex-col' => $embedded && ! $threads,
        ])
    >
        @if ($threads)
            {{-- Layout type IA moderne : sidebar + chat --}}
            <aside class="hidden w-64 shrink-0 flex-col border-r border-synoria-yellow/25 bg-synoria-yellow-mist/40 dark:border-slate-700 dark:bg-slate-950/80 sm:flex">
                <div class="flex items-center justify-between gap-2 border-b border-synoria-yellow/25 px-3 py-3 dark:border-slate-700">
                    <p class="text-xs font-semibold uppercase tracking-wide text-synoria-ink-faint dark:text-gray-400">{{ __('Récent') }}</p>
                    <button type="button" @click="newThread()" class="rounded-lg bg-synoria-ink px-2.5 py-1 text-xs font-semibold text-white dark:bg-synoria-yellow dark:text-synoria-ink">
                        {{ __('Nouveau') }}
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-2 space-y-1">
                    <template x-if="conversations.length === 0">
                        <p class="px-2 py-3 text-xs text-synoria-ink-faint dark:text-gray-500">{{ __('Aucune discussion encore.') }}</p>
                    </template>
                    <template x-for="thread in conversations" :key="thread.id">
                        <div class="group flex items-stretch gap-1">
                            <button
                                type="button"
                                @click="selectThread(thread.id)"
                                class="min-w-0 flex-1 rounded-xl px-3 py-2 text-left text-sm transition"
                                :class="conversationId === thread.id
                                    ? 'bg-white shadow-sm ring-1 ring-synoria-yellow/40 dark:bg-slate-800'
                                    : 'hover:bg-white/70 dark:hover:bg-slate-800/70'"
                            >
                                <span class="block truncate font-medium text-synoria-ink dark:text-gray-100" x-text="thread.title"></span>
                                <span class="mt-0.5 block truncate text-[11px] text-synoria-ink-faint dark:text-gray-500" x-text="thread.preview || ''"></span>
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-2 text-xs text-red-500 opacity-0 group-hover:opacity-100"
                                @click="deleteThread(thread.id)"
                                title="{{ __('Supprimer') }}"
                            >✕</button>
                        </div>
                    </template>
                </div>
                <p class="border-t border-synoria-yellow/20 px-3 py-2 text-[11px] text-synoria-ink-faint dark:border-slate-700 dark:text-gray-500">
                    {{ __('Moteur') }} : <span x-text="engine"></span>
                </p>
            </aside>
        @endif

        <div class="flex min-h-0 min-w-0 flex-1 flex-col">
            <div
                class="flex shrink-0 cursor-grab items-center justify-between gap-3 bg-synoria-ink px-3 py-2.5 text-white active:cursor-grabbing dark:bg-slate-800"
                @unless ($embedded)
                    @mousedown.prevent="startDrag($event)"
                @endunless
            >
                <div class="min-w-0 select-none">
                    <p class="truncate text-sm font-semibold text-synoria-yellow" x-text="agentName"></p>
                    <p class="text-[11px] text-white/70">
                        <span x-text="engine"></span>
                        <span x-show="threads"> · </span>
                        <span x-show="threads" x-text="activeTitle"></span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2" @mousedown.stop>
                    @if ($threads)
                        <button type="button" class="text-xs text-white/70 hover:text-white sm:hidden" @click="newThread()">{{ __('Nouveau') }}</button>
                    @endif
                    <button type="button" class="text-xs text-white/70 hover:text-white" @click="resetChat()">{{ __('Effacer') }}</button>
                    @unless ($embedded)
                        <button type="button" class="text-white/80 hover:text-white" @click="open = false" aria-label="{{ __('Fermer') }}">✕</button>
                    @endunless
                </div>
            </div>

            @if ($threads)
                <div class="flex gap-1 overflow-x-auto border-b border-synoria-yellow/20 px-2 py-2 sm:hidden dark:border-slate-700">
                    <template x-for="thread in conversations" :key="'m'+thread.id">
                        <button
                            type="button"
                            @click="selectThread(thread.id)"
                            class="shrink-0 rounded-full px-3 py-1 text-xs"
                            :class="conversationId === thread.id ? 'bg-synoria-yellow text-synoria-ink' : 'bg-slate-100 text-synoria-ink-soft dark:bg-slate-800 dark:text-gray-300'"
                            x-text="thread.title"
                        ></button>
                    </template>
                </div>
            @endif

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

            <form class="flex shrink-0 gap-2 border-t border-synoria-yellow/25 bg-white p-2.5 dark:border-slate-700 dark:bg-slate-900" @submit.prevent="send()">
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
                    threads: config.threads || false,
                    loading: false,
                    loadingHistory: false,
                    draft: '',
                    messages: [],
                    conversations: [],
                    conversationId: config.conversationId || null,
                    agentName: config.agentName || 'Sara',
                    engine: 'Local',
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
                    get activeTitle() {
                        const t = this.conversations.find((c) => c.id === this.conversationId);
                        return t ? t.title : '{{ __('Nouvelle discussion') }}';
                    },
                    async boot() {
                        this.greeting =
                            'Salut ! Je suis ' + this.agentName + '.\n' +
                            'Tes discussions sont enregistrées ici. Dis-moi un budget ou une envie.';
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
                            if (config.restaurantId) url.searchParams.set('restaurant_id', config.restaurantId);
                            if (this.conversationId) url.searchParams.set('conversation_id', this.conversationId);
                            const res = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (data.agent) this.agentName = data.agent;
                            if (data.engine) this.engine = data.engine;
                            if (Array.isArray(data.conversations)) this.conversations = data.conversations;
                            if (data.conversation_id) this.conversationId = data.conversation_id;
                            if (Array.isArray(data.history)) this.messages = data.history;
                            this.$nextTick(() => this.scroll());
                        } catch (e) {
                        } finally {
                            this.loadingHistory = false;
                        }
                    },
                    async selectThread(id) {
                        this.conversationId = id;
                        this.messages = [];
                        await this.loadHistory();
                    },
                    async newThread() {
                        try {
                            const res = await fetch(config.storeConversationUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({ restaurant_id: config.restaurantId }),
                            });
                            const data = await res.json();
                            if (data.conversations) this.conversations = data.conversations;
                            if (data.conversation) {
                                this.conversationId = data.conversation.id;
                                this.messages = [];
                            }
                        } catch (e) {}
                    },
                    async deleteThread(id) {
                        try {
                            const res = await fetch(config.destroyConversationUrlBase + '/' + id, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                            const data = await res.json();
                            if (data.conversations) this.conversations = data.conversations;
                            if (this.conversationId === id) {
                                this.conversationId = data.conversations[0]?.id || null;
                                this.messages = [];
                                if (this.conversationId) await this.loadHistory();
                            }
                        } catch (e) {}
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
                                    conversation_id: this.conversationId,
                                }),
                            });
                            const data = await res.json();
                            if (!res.ok) throw new Error(data.message || 'Erreur agent');
                            if (data.agent) this.agentName = data.agent;
                            if (data.engine) this.engine = data.engine;
                            if (data.conversation_id) this.conversationId = data.conversation_id;
                            if (Array.isArray(data.conversations)) this.conversations = data.conversations;
                            this.messages.push({ role: 'assistant', content: data.reply });
                            this.$nextTick(() => this.scroll());
                        } catch (e) {
                            this.messages.push({
                                role: 'assistant',
                                content: 'Désolée, j’ai eu un blanc. Réessaie juste après.',
                            });
                        } finally {
                            this.loading = false;
                            this.$nextTick(() => {
                                this.scroll();
                                // Remet le focus sur le champ (évite l’impression que Sara est « bloquée »)
                                const input = this.$el.querySelector('input[type="text"]');
                                if (input) input.focus();
                            });
                        }
                    },
                    async resetChat() {
                        this.messages = [];
                        try {
                            const body = this.conversationId
                                ? JSON.stringify({ conversation_id: this.conversationId })
                                : '{}';
                            const res = await fetch(config.resetUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': config.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body,
                            });
                            const data = await res.json();
                            if (data.conversations) this.conversations = data.conversations;
                            this.conversationId = null;
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
                        const lines = escaped.split(/\n/);
                        let html = '';
                        let inList = false;
                        for (const line of lines) {
                            const listMatch = line.match(/^\s*[-•]\s+(.+)$/);
                            if (listMatch) {
                                if (!inList) { html += '<ul>'; inList = true; }
                                html += '<li>' + listMatch[1] + '</li>';
                            } else {
                                if (inList) { html += '</ul>'; inList = false; }
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
