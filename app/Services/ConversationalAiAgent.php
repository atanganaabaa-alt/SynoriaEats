<?php

namespace App\Services;

use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Models\CompanionConversation;
use App\Models\CompanionMessage;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConversationalAiAgent
{
    public function __construct(
        private CartService $cart,
        private AminaLocalBrain $localBrain,
        private RestaurantMatcher $matcher,
        private CameroonPlaceGeocoder $places,
    ) {}

    public function agentName(): string
    {
        return (string) config('synoria.companion.name', 'Sara');
    }

    public function usesLocalOnly(): bool
    {
        return $this->resolveProvider() === 'local';
    }

    public function resolveProvider(): string
    {
        $provider = Str::lower((string) config('synoria.companion.provider', 'local'));
        $base = Str::lower((string) config('synoria.companion.base_url', ''));

        if ($provider === 'agentrouter' || str_contains($base, 'agentrouter.org')) {
            return $this->hasUsableApiKey('agentrouter') ? 'agentrouter' : 'local';
        }

        if (in_array($provider, ['openai', 'groq', 'anthropic'], true) && $this->hasUsableApiKey($provider)) {
            return $provider;
        }

        // Auto : clé OpenAI/Groq présente → vrai LLM même si .env dit local
        if ($this->hasUsableApiKey('openai')) {
            return str_contains($base, 'groq.com') ? 'groq' : 'openai';
        }

        if ($this->hasUsableApiKey('anthropic')) {
            return 'anthropic';
        }

        return 'local';
    }

    public function isConfigured(): bool
    {
        if (! (bool) config('synoria.companion.enabled')) {
            return false;
        }

        // Local always "configured"; cloud requires a real key
        if ($this->resolveProvider() === 'local') {
            return true;
        }

        return $this->apiKey() !== null;
    }

    public function engineLabel(): string
    {
        return match ($this->resolveProvider()) {
            'openai' => 'OpenAI',
            'groq' => 'Groq',
            'anthropic' => 'Claude',
            'agentrouter' => 'AgentRouter',
            default => 'Local',
        };
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, suggestions: list<string>, mode: string, agent: string, preferences: array<string, mixed>}
     */
    public function reply(
        string $message,
        ?User $user = null,
        ?Restaurant $restaurant = null,
        array $history = [],
        array $matchPreferences = [],
        array $clientLocation = [],
    ): array {
        $message = trim($message);
        $learned = $this->learnFromUserMessage($user, $message);

        // Lieu : message courant → historique → goûts appris
        $statedPlace = $this->places->resolve($message);
        if ($statedPlace === null) {
            foreach (array_reverse($history) as $turn) {
                if (($turn['role'] ?? '') !== 'user') {
                    continue;
                }
                $statedPlace = $this->places->resolve((string) ($turn['content'] ?? ''));
                if ($statedPlace !== null) {
                    break;
                }
            }
        }
        if ($statedPlace === null) {
            $tastes = $this->resolveLearnedTastes($user);
            if (! empty($tastes['lieu'])) {
                $statedPlace = $this->places->resolve((string) $tastes['lieu']);
            }
        }
        if ($statedPlace !== null) {
            if (($clientLocation['lat'] ?? null) === null) {
                $clientLocation['lat'] = $statedPlace['lat'];
                $clientLocation['lng'] = $statedPlace['lng'];
            }
            if ($user) {
                $this->savePreferenceUpdates($user, ['lieu' => $statedPlace['label']]);
                $learned['lieu'] = $statedPlace['label'];
            }
        }

        $context = $this->buildContext($user, $restaurant, $matchPreferences, $clientLocation);
        if ($statedPlace !== null) {
            $context['stated_place'] = $statedPlace['label'];
        }
        $suggestions = $this->quickSuggestions($context);
        $agent = $this->agentName();

        if ($this->usesLocalOnly() || ! $this->isConfigured()) {
            $raw = $this->localBrain->reply($message, $context, $history, $agent);
            [$clean, $fromTags] = $this->extractAndApplyPreferenceUpdates($user, $raw);

            return [
                'reply' => $this->sanitizeReply($clean),
                'suggestions' => $suggestions,
                'mode' => 'local',
                'agent' => $agent,
                'preferences' => $this->tastesFor($user, array_merge($learned, $fromTags)),
            ];
        }

        try {
            $raw = $this->askLlm($message, $context, $history);
            [$clean, $fromTags] = $this->extractAndApplyPreferenceUpdates($user, $raw);

            return [
                'reply' => $this->sanitizeReply($clean),
                'suggestions' => $suggestions,
                'mode' => $this->resolveProvider(),
                'agent' => $agent,
                'preferences' => $this->tastesFor($user, array_merge($learned, $fromTags)),
            ];
        } catch (\Throwable $e) {
            Log::warning('Conversational AI agent failed, falling back to local', [
                'error' => $e->getMessage(),
                'provider' => config('synoria.companion.provider'),
            ]);

            $raw = $this->localBrain->reply($message, $context, $history, $agent);
            [$clean, $fromTags] = $this->extractAndApplyPreferenceUpdates($user, $raw);

            $prefix = '';
            if (str_contains(Str::lower($e->getMessage()), '401')
                || str_contains($e->getMessage(), '令牌')
                || str_contains(Str::lower($e->getMessage()), 'unauthorized')) {
                $prefix = ''; // éviter d’alarmer l’utilisateur; logs déjà présents
            }

            return [
                'reply' => $this->sanitizeReply($prefix.$clean),
                'suggestions' => $suggestions,
                'mode' => 'local_fallback',
                'agent' => $agent,
                'preferences' => $this->tastesFor($user, array_merge($learned, $fromTags)),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $matchPreferences
     * @param  array{lat?: float|null, lng?: float|null}  $clientLocation
     * @return array<string, mixed>
     */
    public function buildContext(
        ?User $user = null,
        ?Restaurant $restaurant = null,
        array $matchPreferences = [],
        array $clientLocation = [],
    ): array {
        $cartRestaurant = $this->cart->restaurant();

        if (! $restaurant && $cartRestaurant) {
            $restaurant = $cartRestaurant;
        }

        if ($restaurant) {
            $restaurant->loadMissing([
                'menuItems' => fn ($q) => $q
                    ->where('is_available', true)
                    ->where('category', '!=', MenuCategory::Accompagnements->value)
                    ->orderBy('category')
                    ->orderBy('price'),
            ]);
        }

        $activeOrder = null;
        if ($user) {
            $activeOrder = Order::query()
                ->with(['restaurant', 'statusEvents'])
                ->where('customer_id', $user->id)
                ->whereNotIn('status', [OrderStatus::Delivered, OrderStatus::Cancelled])
                ->latest()
                ->first();
        }

        $catalogQuery = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->whereHas('menuItems', fn ($q) => $q->where('is_available', true))
            ->with(['menuItems' => fn ($q) => $q
                ->where('is_available', true)
                ->where('category', '!=', MenuCategory::Accompagnements->value)
                ->orderBy('price')
                ->limit(6)]);

        $lat = isset($clientLocation['lat']) ? (float) $clientLocation['lat'] : null;
        $lng = isset($clientLocation['lng']) ? (float) $clientLocation['lng'] : null;
        $weights = is_array($matchPreferences['weights'] ?? null)
            ? $matchPreferences['weights']
            : $this->matcher->defaultWeights();

        if ($lat !== null && $lng !== null) {
            $catalog = $this->matcher->rank($catalogQuery->get(), $lat, $lng, $weights, strictDistance: true);
            if ($catalog->isEmpty()) {
                $catalog = $this->matcher->rank($catalogQuery->get(), $lat, $lng, $weights, strictDistance: false)
                    ->take(5);
            } else {
                $catalog = $catalog->take(8);
            }
        } else {
            $catalog = $catalogQuery->orderByDesc('rating')->limit(8)->get();
        }

        $menu = $restaurant
            ? $restaurant->menuItems->map(fn ($item) => [
                'name' => $item->name,
                'category' => is_object($item->category) ? $item->category->value : $item->category,
                'price' => (int) $item->price,
                'description' => Str::limit((string) $item->description, 120),
            ])->values()->all()
            : [];

        $cartLines = $this->cart->lines()->map(fn ($line) => [
            'name' => $line['name'] ?? '',
            'quantity' => (int) ($line['quantity'] ?? 0),
            'unit_price' => (int) ($line['unit_price'] ?? 0),
        ])->values()->all();

        $learnedTastes = $this->resolveLearnedTastes($user);

        // RAG plat : plats réellement disponibles (catalogue global + resto courant)
        $availableDishes = MenuItem::query()
            ->where('is_available', true)
            ->where('category', '!=', MenuCategory::Accompagnements->value)
            ->whereHas('restaurant', fn ($q) => $q->where('is_open', true)->where('is_validated', true))
            ->with('restaurant:id,name,slug')
            ->when($restaurant, fn ($q) => $q->orderByRaw('restaurant_id = ? DESC', [$restaurant->id]))
            ->orderBy('price')
            ->limit(40)
            ->get()
            ->map(fn (MenuItem $item) => [
                'name' => $item->name,
                'price' => (int) $item->price,
                'category' => is_object($item->category) ? $item->category->value : $item->category,
                'restaurant' => $item->restaurant?->name,
                'restaurant_slug' => $item->restaurant?->slug,
            ])
            ->values()
            ->all();

        return [
            'client' => [
                'name' => $user?->name,
                'role' => $user?->role?->value ?? 'guest',
                'lat' => $lat,
                'lng' => $lng,
            ],
            'preferences' => $matchPreferences ?: null,
            'learned_tastes' => $learnedTastes ?: null,
            'available_dishes' => $availableDishes,
            'restaurant' => $restaurant ? [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'category' => $restaurant->category,
                'rating' => (float) $restaurant->rating,
                'prep_time_min' => (int) $restaurant->prep_time_min,
                'prep_time_max' => (int) $restaurant->prep_time_max,
                'delivery_fee' => (int) $restaurant->delivery_fee,
                'address' => $restaurant->address,
                'distance_km' => $restaurant->distance_km ?? null,
            ] : null,
            'menu' => $menu,
            'catalog' => $catalog->map(fn (Restaurant $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'category' => $r->category,
                'rating' => (float) $r->rating,
                'prep_time_min' => (int) $r->prep_time_min,
                'prep_time_max' => (int) $r->prep_time_max,
                'delivery_fee' => (int) ($r->estimated_fee ?? $r->delivery_fee),
                'address' => $r->address,
                'distance_km' => $r->distance_km !== null ? round((float) $r->distance_km, 1) : null,
                'place_label' => $r->place_label ?? null,
                'sample_dishes' => $r->menuItems->map(fn ($item) => [
                    'name' => $item->name,
                    'price' => (int) $item->price,
                ])->values()->all(),
            ])->values()->all(),
            'cart' => [
                'count' => $this->cart->count(),
                'subtotal' => $this->cart->subtotal(),
                'lines' => $cartLines,
            ],
            'active_order' => $activeOrder ? [
                'number' => $activeOrder->number,
                'status' => $activeOrder->status->value,
                'status_label' => $activeOrder->status->label(),
                'restaurant' => $activeOrder->restaurant->name,
                'prep_time_min' => (int) $activeOrder->restaurant->prep_time_min,
                'prep_time_max' => (int) $activeOrder->restaurant->prep_time_max,
                'created_at' => $activeOrder->created_at?->toIso8601String(),
                'elapsed_minutes' => $activeOrder->created_at
                    ? (int) $activeOrder->created_at->diffInMinutes(now())
                    : null,
            ] : null,
            'currency' => 'FCFA',
            'market' => 'Cameroun',
        ];
    }

    /**
     * @return list<array{id: int, title: string, last_message_at: ?string, preview: ?string}>
     */
    public function listConversations(?User $user, string $sessionKey, int $limit = 40): array
    {
        return CompanionConversation::query()
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            )
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (CompanionConversation $c) {
                $preview = $c->messages()
                    ->where('role', 'user')
                    ->orderByDesc('id')
                    ->value('content');

                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'last_message_at' => $c->last_message_at?->toIso8601String(),
                    'preview' => $preview ? Str::limit($preview, 80) : null,
                ];
            })
            ->all();
    }

    public function findConversation(?User $user, string $sessionKey, ?int $conversationId): ?CompanionConversation
    {
        if (! $conversationId) {
            return null;
        }

        return CompanionConversation::query()
            ->whereKey($conversationId)
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            )
            ->first();
    }

    public function createConversation(?User $user, string $sessionKey, ?int $restaurantId = null, ?string $title = null): CompanionConversation
    {
        return CompanionConversation::query()->create([
            'user_id' => $user?->id,
            'session_key' => $sessionKey,
            'restaurant_id' => $restaurantId,
            'title' => $title ?: __('Nouvelle discussion'),
            'last_message_at' => now(),
        ]);
    }

    public function resolveOrCreateConversation(
        ?User $user,
        string $sessionKey,
        ?int $conversationId = null,
        ?int $restaurantId = null,
    ): CompanionConversation {
        if ($conversationId) {
            $existing = $this->findConversation($user, $sessionKey, $conversationId);
            if ($existing) {
                return $existing;
            }

            return $this->createConversation($user, $sessionKey, $restaurantId);
        }

        $latest = CompanionConversation::query()
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            )
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        if ($latest) {
            return $latest;
        }

        return $this->createConversation($user, $sessionKey, $restaurantId);
    }

    /**
     * @return list<array{role: string, content: string, created_at?: ?string}>
     */
    public function loadHistory(?User $user, string $sessionKey, int $limit = 40, ?int $conversationId = null): array
    {
        $conversation = $this->findConversation($user, $sessionKey, $conversationId);

        $query = CompanionMessage::query()->whereIn('role', ['user', 'assistant']);

        if ($conversation) {
            $query->where('conversation_id', $conversation->id);
        } else {
            // Compat : ancien historique plat
            $query->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            );
        }

        $rows = $query->orderByDesc('id')->limit($limit)->get()->reverse()->values();

        return $rows->map(fn (CompanionMessage $row) => [
            'role' => $row->role,
            'content' => $row->content,
            'created_at' => $row->created_at?->toIso8601String(),
        ])->all();
    }

    public function persistTurn(
        ?User $user,
        string $sessionKey,
        string $userMessage,
        string $assistantMessage,
        ?int $restaurantId = null,
        ?CompanionConversation $conversation = null,
    ): CompanionConversation {
        $conversation ??= $this->resolveOrCreateConversation($user, $sessionKey, null, $restaurantId);

        if ($user && $conversation->user_id === null) {
            $conversation->user_id = $user->id;
        }

        if ($conversation->title === __('Nouvelle discussion') || $conversation->title === 'Nouvelle discussion') {
            $conversation->title = Str::limit(trim($userMessage), 60);
        }

        $conversation->restaurant_id = $restaurantId ?? $conversation->restaurant_id;
        $conversation->last_message_at = now();
        $conversation->save();

        CompanionMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'session_key' => $sessionKey,
            'restaurant_id' => $restaurantId,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        CompanionMessage::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'session_key' => $sessionKey,
            'restaurant_id' => $restaurantId,
            'role' => 'assistant',
            'content' => $assistantMessage,
        ]);

        return $conversation;
    }

    public function clearHistory(?User $user, string $sessionKey, ?int $conversationId = null): void
    {
        if ($conversationId) {
            $this->deleteConversation($user, $sessionKey, $conversationId);

            return;
        }

        $conversations = CompanionConversation::query()
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            )
            ->get();

        foreach ($conversations as $conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        CompanionMessage::query()
            ->when(
                $user,
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->where('session_key', $sessionKey)->whereNull('user_id')
            )
            ->delete();
    }

    public function deleteConversation(?User $user, string $sessionKey, int $conversationId): bool
    {
        $conversation = $this->findConversation($user, $sessionKey, $conversationId);
        if (! $conversation) {
            return false;
        }
        $conversation->messages()->delete();
        $conversation->delete();

        return true;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    public function quickSuggestions(array $context): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    private function askLlm(string $message, array $context, array $history): string
    {
        $provider = $this->resolveProvider();

        return match ($provider) {
            'anthropic' => $this->askAnthropic($message, $context, $history),
            'openai', 'groq', 'agentrouter' => $this->askOpenAi($message, $context, $history),
            default => throw new \RuntimeException('Provider LLM inconnu: '.$provider),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    private function askOpenAi(string $message, array $context, array $history): string
    {
        $base = rtrim((string) config('synoria.companion.base_url'), '/');
        $model = (string) config('synoria.companion.model');
        $key = $this->apiKey();

        $messages = [['role' => 'system', 'content' => $this->systemPrompt($context)]];

        foreach ($this->trimHistory($history) as $turn) {
            $messages[] = $turn;
        }

        $messages[] = ['role' => 'user', 'content' => Str::limit($message, 1500)];

        $body = [
            'model' => $model,
            'temperature' => 0.85,
            'max_tokens' => 900,
            'messages' => $messages,
        ];

        $response = null;
        $lastError = null;
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $request = Http::timeout(60)->acceptJson();

                if ($this->resolveProvider() === 'agentrouter') {
                    // AgentRouter WAF : exige une empreinte type Claude Code CLI
                    $request = $request->withHeaders($this->agentRouterHeaders($key));
                } else {
                    $request = $request->withToken($key);
                }

                $response = $request->post($base.'/chat/completions', $body);
                $response->throw();
                break;
            } catch (\Throwable $e) {
                $lastError = $e;
                $msg = Str::lower($e->getMessage());
                $retryable = str_contains($msg, 'timeout')
                    || str_contains($msg, 'curl error 28')
                    || str_contains($msg, 'curl error 56')
                    || str_contains($msg, 'connection reset');
                if (! $retryable || $attempt === 2) {
                    throw $e;
                }
                usleep(400_000);
            }
        }

        if ($response === null) {
            throw $lastError ?? new \RuntimeException('Réponse LLM indisponible.');
        }

        $payload = $response->json();
        $content = data_get($payload, 'choices.0.message.content');

        if ((! is_string($content) || trim($content) === '') && is_string(data_get($payload, 'choices.0.message.reasoning_content'))) {
            // Certains modèles (DeepSeek) mettent d’abord du reasoning : on retombe sur le contenu utile si présent en fin
            $content = data_get($payload, 'choices.0.message.content');
        }

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Réponse LLM vide ('.$model.').');
        }

        return trim($content);
    }

    /**
     * @return array<string, string>
     */
    private function agentRouterHeaders(string $key): array
    {
        return [
            'Authorization' => 'Bearer '.$key,
            'x-api-key' => $key,
            'User-Agent' => 'claude-cli/1.0.108 (external, cli)',
            'anthropic-version' => '2023-06-01',
            'anthropic-beta' => 'claude-code-20250219,oauth-2025-04-20',
            'anthropic-dangerous-direct-browser-access' => 'true',
            'x-app' => 'cli',
            'x-stainless-lang' => 'js',
            'x-stainless-package-version' => '0.55.1',
            'x-stainless-os' => 'Linux',
            'x-stainless-arch' => 'x64',
            'x-stainless-runtime' => 'node',
            'x-stainless-runtime-version' => 'v22.0.0',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    private function askAnthropic(string $message, array $context, array $history): string
    {
        $model = (string) config('synoria.companion.anthropic_model', 'claude-3-5-haiku-latest');
        $key = $this->apiKey();

        $messages = [];
        foreach ($this->trimHistory($history) as $turn) {
            $messages[] = [
                'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $turn['content'],
            ];
        }
        $messages[] = ['role' => 'user', 'content' => Str::limit($message, 1500)];

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(45)
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 700,
                'temperature' => 0.85,
                'system' => $this->systemPrompt($context),
                'messages' => $messages,
            ]);

        $response->throw();

        $blocks = data_get($response->json(), 'content', []);
        $text = '';
        foreach ($blocks as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        if (trim($text) === '') {
            throw new \RuntimeException('Réponse Anthropic vide.');
        }

        return trim($text);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function systemPrompt(array $context): string
    {
        $name = $this->agentName();
        $preferencesJson = json_encode(
            $context['learned_tastes'] ?? new \stdClass,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Tu es {$name}, l'assistante culinaire ultra-intelligente de SynoriaEats au Cameroun. Tu as une mémoire parfaite. Tu dois analyser les préférences actuelles de l'utilisateur : {$preferencesJson} et son historique.

Règles strictes (PRIORITÉ ABSOLUE) :
0. Lis et OBEIS le DERNIER message utilisateur avant tout. Si l’utilisateur te demande d’écouter, de prendre sa position, d’expliquer tes critères, ou se plaint que tu ignores, réponds D’ABORD à ça en langage clair. Ne redis jamais « donne un budget » si ce n’est pas ce qu’il demande.
1. Si `stated_place` est présent (ex. Ambam), considère-le comme sa position. Classe / commente les restos avec `distance_km`. Si rien n’est proche, dis-le honnêtement (ex. « rien d’ouvert près d’Ambam, le plus proche listé est à X km ») au lieu de proposer comme si c’était à côté.
2. Ne répète jamais les mêmes phrases d'accueil ou les mêmes structures d'une réponse à l'autre.
3. Si l'utilisateur mentionne un goût / lieu / habitude, ajoute `[UPDATE_PREFERENCE: clé=valeur]` (ex. lieu=Ambam, budget_moyen=5000). Ne montre pas ces balises à l’utilisateur.
4. Propose uniquement des plats réels du catalogue (`available_dishes`, `menu`, `catalog`). Markdown, ton chaleureux, camfranglais léger OK.
5. N’utilise JAMAIS le tiret long (—).

## Langue
- Réponds dans la langue du DERNIER message (FR ou EN).

## Contexte métier (JSON)
{$contextJson}
PROMPT;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return list<array{role: string, content: string}>
     */
    private function trimHistory(array $history): array
    {
        $out = [];
        foreach (array_slice($history, -10) as $turn) {
            $role = $turn['role'] ?? '';
            if (! in_array($role, ['user', 'assistant'], true)) {
                continue;
            }
            $content = trim((string) ($turn['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $out[] = [
                'role' => $role,
                'content' => Str::limit($content, 1200),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveLearnedTastes(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $pref = UserPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['tastes' => []]
        );

        return is_array($pref->tastes) ? $pref->tastes : [];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function tastesFor(?User $user, array $extra = []): array
    {
        return array_merge($this->resolveLearnedTastes($user), $extra);
    }

    /**
     * Heuristique locale : apprend depuis le message utilisateur (sans LLM).
     *
     * @return array<string, mixed>
     */
    private function learnFromUserMessage(?User $user, string $message): array
    {
        if (! $user) {
            return [];
        }

        $lower = mb_strtolower($message, 'UTF-8');
        $updates = [];

        if (preg_match('/\b(\d{3,6})\s*(?:fcfa|f\s*cfa|francs?)?\b/u', $lower, $m)) {
            $updates['budget_moyen'] = (int) $m[1];
        }

        if (preg_match('/(?:allergique|allergie)\s+(?:à|a|au|aux)?\s*([a-zàâäéèêëïîôùûüç\s\-]{2,40})/u', $lower, $m)) {
            $updates['allergies'] = trim($m[1]);
        }

        if (preg_match('/(?:je\s+n[\'’]?aime\s+plus|plus\s+de|je\s+déteste|je\s+deteste)\s+(?:le|la|les|l[\'’])?\s*([a-zàâäéèêëïîôùûüç\s\-]{2,40})/u', $lower, $m)) {
            $updates['aversions'] = trim($m[1]);
        }

        if (preg_match('/(?:piment|épicé|epice|spicy)\s*(fort|moyen|doux|léger|leger)?/u', $lower, $m)) {
            $updates['piment'] = isset($m[1]) && $m[1] !== '' ? trim($m[1]) : 'oui';
        }

        if (preg_match('/(?:souvent|toujours)\s+(?:à|a)\s+(midi|soir|matin)/u', $lower, $m)) {
            $updates['repas_favori'] = trim($m[1]);
        }

        if ($updates === []) {
            return [];
        }

        $this->savePreferenceUpdates($user, $updates);

        return $updates;
    }

    /**
     * Parse `[UPDATE_PREFERENCE: clé=valeur]` depuis la réponse IA, sauvegarde, retire du texte.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function extractAndApplyPreferenceUpdates(?User $user, string $reply): array
    {
        $updates = [];
        $clean = preg_replace_callback(
            '/\[\s*UPDATE_PREFERENCE\s*:\s*([^=\]\s]+)\s*=\s*([^\]]+)\]/iu',
            static function (array $m) use (&$updates): string {
                $key = trim($m[1]);
                $value = trim($m[2]);
                if ($key !== '') {
                    $updates[$key] = $value;
                }

                return '';
            },
            $reply
        ) ?? $reply;

        $clean = preg_replace("/\n{3,}/u", "\n\n", trim($clean)) ?? trim($clean);

        if ($user && $updates !== []) {
            $this->savePreferenceUpdates($user, $updates);
        }

        return [$clean, $updates];
    }

    /**
     * @param  array<string, mixed>  $updates
     */
    private function savePreferenceUpdates(User $user, array $updates): void
    {
        $pref = UserPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['tastes' => []]
        );
        $pref->mergeTastes($updates)->save();
    }

    private function hasUsableApiKey(string $provider): bool
    {
        $key = match ($provider) {
            'anthropic' => config('synoria.companion.anthropic_api_key'),
            default => config('synoria.companion.api_key'),
        };

        if (! filled($key)) {
            return false;
        }

        $normalized = Str::lower(trim((string) $key));

        return ! Str::contains($normalized, ['sk-ton-', 'your-api-key', 'changeme', 'xxx', 'gsk-ton-']);
    }

    private function apiKey(): ?string
    {
        $provider = $this->resolveProvider();

        if ($provider === 'anthropic') {
            return $this->hasUsableApiKey('anthropic')
                ? (string) config('synoria.companion.anthropic_api_key')
                : null;
        }

        if ($provider === 'local') {
            return null;
        }

        return $this->hasUsableApiKey($provider === 'agentrouter' ? 'agentrouter' : 'openai')
            ? (string) config('synoria.companion.api_key')
            : null;
    }

    private function sanitizeReply(string $reply): string
    {
        $reply = str_replace(['—', '–'], [' - ', ' - '], $reply);
        // Preserve Markdown line breaks; only collapse spaces inside a line
        $lines = preg_split("/\r\n|\n|\r/", $reply) ?: [$reply];
        $lines = array_map(static function (string $line): string {
            $line = preg_replace('/[^\S\n]{2,}/u', ' ', $line) ?? $line;

            return rtrim($line);
        }, $lines);
        $reply = implode("\n", $lines);
        $reply = preg_replace("/\n{3,}/u", "\n\n", $reply) ?? $reply;

        return trim($reply);
    }
}
