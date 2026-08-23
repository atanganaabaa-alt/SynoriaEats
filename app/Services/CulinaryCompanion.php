<?php

namespace App\Services;

use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CulinaryCompanion
{
    public function __construct(private CartService $cart) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{reply: string, suggestions: list<string>, mode: string}
     */
    public function reply(string $message, ?User $user = null, ?Restaurant $restaurant = null, array $history = []): array
    {
        $message = trim($message);
        $context = $this->buildContext($user, $restaurant);

        if ($this->openaiEnabled()) {
            try {
                $reply = $this->askOpenAi($message, $context, $history);

                return [
                    'reply' => $reply,
                    'suggestions' => $this->quickSuggestions($context),
                    'mode' => 'openai',
                ];
            } catch (\Throwable $e) {
                Log::warning('Companion OpenAI fallback', ['error' => $e->getMessage()]);
            }
        }

        return [
            'reply' => $this->localReply($message, $context),
            'suggestions' => $this->quickSuggestions($context),
            'mode' => 'local',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildContext(?User $user = null, ?Restaurant $restaurant = null): array
    {
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

        $menu = $restaurant
            ? $restaurant->menuItems->map(fn ($item) => [
                'name' => $item->name,
                'category' => $item->category,
                'price' => (int) $item->price,
                'description' => Str::limit((string) $item->description, 80),
            ])->values()->all()
            : [];

        $cartLines = $this->cart->lines()->map(fn ($line) => [
            'name' => $line['name'] ?? '',
            'quantity' => (int) ($line['quantity'] ?? 0),
            'unit_price' => (int) ($line['unit_price'] ?? 0),
        ])->values()->all();

        return [
            'restaurant' => $restaurant ? [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'category' => $restaurant->category,
                'rating' => (float) $restaurant->rating,
                'prep_time_min' => (int) $restaurant->prep_time_min,
                'prep_time_max' => (int) $restaurant->prep_time_max,
                'delivery_fee' => (int) $restaurant->delivery_fee,
            ] : null,
            'menu' => $menu,
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
                    ? $activeOrder->created_at->diffInMinutes(now())
                    : null,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function localReply(string $message, array $context): string
    {
        $lower = Str::lower($message);
        $budget = $this->extractBudget($message);

        if ($budget !== null) {
            return $this->budgetAdvice($budget, $context);
        }

        if (Str::contains($lower, ['attente', 'temps', 'long', 'combien de temps', 'délai', 'delai', 'préparation', 'preparation', 'livraison'])) {
            return $this->waitAdvice($context);
        }

        if (Str::contains($lower, ['panier', 'cart', 'commande en cours', 'déjà', 'deja'])) {
            return $this->cartAdvice($context);
        }

        if (Str::contains($lower, ['boisson', 'boissons', 'drink'])) {
            return $this->categoryAdvice(MenuCategory::Boissons->value, $context, 'Voici des boissons du menu');
        }

        if (Str::contains($lower, ['dessert', 'sucré', 'sucre'])) {
            return $this->categoryAdvice(MenuCategory::Desserts->value, $context, 'Pour terminer en douceur');
        }

        if (Str::contains($lower, ['meilleur', 'recommande', 'conseille', 'idée', 'idee', 'quoi manger', 'suggestion', 'plat'])) {
            return $this->recommendDishes($context);
        }

        if ($context['restaurant']) {
            return $this->welcomeRestaurant($context).' '.$this->recommendDishes($context);
        }

        return "Je suis ton compagnon SynoriaEats. Dis-moi ton budget (ex. « 5000 FCFA »), "
            ."demande une idée de plat, le temps d’attente, ou ouvre un restaurant pour que je m’appuie sur son menu.";
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function welcomeRestaurant(array $context): string
    {
        $r = $context['restaurant'];

        return "Chez {$r['name']} (★ {$r['rating']}), compte environ {$r['prep_time_min']}–{$r['prep_time_max']} min de préparation.";
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function budgetAdvice(int $budget, array $context): string
    {
        $menu = collect($context['menu']);

        if ($menu->isEmpty()) {
            return "Pour un budget de {$this->money($budget)}, ouvre un restaurant : je te proposerai des plats qui tiennent dans cette enveloppe.";
        }

        $fee = (int) ($context['restaurant']['delivery_fee'] ?? 0);
        $foodBudget = max(0, $budget - $fee);

        $affordable = $menu
            ->filter(fn ($item) => (int) $item['price'] <= $foodBudget)
            ->sortBy('price')
            ->take(5)
            ->values();

        if ($affordable->isEmpty()) {
            $cheapest = $menu->sortBy('price')->first();

            return "Avec {$this->money($budget)} (frais de base ~{$this->money($fee)}), "
                ."c’est serré. Le plus accessible ici : {$cheapest['name']} à {$this->money($cheapest['price'])}.";
        }

        $lines = $affordable->map(
            fn ($item) => "• {$item['name']} — {$this->money($item['price'])}".($item['category'] ? " ({$item['category']})" : '')
        )->implode("\n");

        return "Pour ~{$this->money($budget)} (dont ~{$this->money($fee)} de frais de base), tu peux viser :\n{$lines}\n"
            .'Dis-moi si tu préfères un plat copieux, une boisson, ou un combo léger.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function waitAdvice(array $context): string
    {
        if ($context['active_order']) {
            $o = $context['active_order'];
            $elapsed = $o['elapsed_minutes'] ?? 0;
            $min = $o['prep_time_min'];
            $max = $o['prep_time_max'];

            return "Ta commande {$o['number']} est « {$o['status_label']} » chez {$o['restaurant']}. "
                ."Elle tourne depuis environ {$elapsed} min. "
                ."Préparation habituelle : {$min}–{$max} min, puis le temps de livraison selon le livreur. "
                .'Tu peux suivre le détail sur la page de la commande.';
        }

        if ($context['restaurant']) {
            $r = $context['restaurant'];

            return "Chez {$r['name']}, la cuisine annonce {$r['prep_time_min']}–{$r['prep_time_max']} min. "
                .'Ajoute le trajet livreur une fois la commande prête. Les pics midi/soir peuvent allonger un peu.';
        }

        return 'Ouvre un restaurant ou passe une commande : je pourrai estimer l’attente à partir du temps de préparation indiqué.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cartAdvice(array $context): string
    {
        $cart = $context['cart'];
        if (($cart['count'] ?? 0) === 0) {
            return 'Ton panier est vide. Choisis un restaurant et je t’aiderai à composer un menu.';
        }

        $lines = collect($cart['lines'])->map(
            fn ($line) => "• {$line['quantity']}× {$line['name']} ({$this->money($line['unit_price'])})"
        )->implode("\n");

        return "Dans ton panier :\n{$lines}\nSous-total : {$this->money($cart['subtotal'])}. "
            .'Tu veux rester dans un budget précis, ou ajouter une boisson / un dessert ?';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function categoryAdvice(string $category, array $context, string $intro): string
    {
        $items = collect($context['menu'])->where('category', $category)->sortBy('price')->take(5)->values();

        if ($items->isEmpty()) {
            return "Je ne vois pas encore de {$category} disponibles sur ce menu.";
        }

        $lines = $items->map(fn ($item) => "• {$item['name']} — {$this->money($item['price'])}")->implode("\n");

        return "{$intro} :\n{$lines}";
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recommendDishes(array $context): string
    {
        $menu = collect($context['menu']);
        if ($menu->isEmpty()) {
            return 'Parcours le catalogue des restaurants, puis reviens : je te recommanderai selon le menu réel.';
        }

        $plats = $menu->where('category', MenuCategory::Plats->value);
        $pool = $plats->isNotEmpty() ? $plats : $menu;
        $picks = $pool->sortBy('price')->values();

        $mid = (int) floor(max(0, ($picks->count() - 1) / 2));
        $selected = collect([
            $picks->first(),
            $picks->get($mid),
            $picks->last(),
        ])->filter()->unique('name')->take(3);

        $lines = $selected->map(fn ($item) => "• {$item['name']} — {$this->money($item['price'])}")->implode("\n");

        return "Quelques idées adaptées à ce menu :\n{$lines}\n"
            .'Tu peux aussi me donner un budget, par ex. « j’ai 4000 FCFA ».';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function quickSuggestions(array $context): array
    {
        $suggestions = ['J’ai 5000 FCFA', 'Que me recommandes-tu ?', 'Combien de temps d’attente ?'];

        if (($context['cart']['count'] ?? 0) > 0) {
            $suggestions[] = 'Que contient mon panier ?';
        }

        if ($context['active_order']) {
            $suggestions[] = 'Où en est ma commande ?';
        }

        return array_values(array_unique($suggestions));
    }

    private function extractBudget(string $message): ?int
    {
        if (preg_match('/(\d[\d\s]{2,})\s*(fcfa|f\b|francs?)?/iu', $message, $matches)) {
            $digits = (int) preg_replace('/\D+/', '', $matches[1]);

            return $digits >= 500 ? $digits : null;
        }

        return null;
    }

    private function money(int $amount): string
    {
        return number_format($amount, 0, ',', ' ').' FCFA';
    }

    private function openaiEnabled(): bool
    {
        return (bool) config('synoria.companion.enabled')
            && filled(config('synoria.companion.api_key'));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    private function askOpenAi(string $message, array $context, array $history): string
    {
        $base = rtrim((string) config('synoria.companion.base_url'), '/');
        $model = (string) config('synoria.companion.model');
        $key = (string) config('synoria.companion.api_key');

        $system = 'Tu es le compagnon culinaire SynoriaEats (Cameroun, FCFA). '
            .'Réponds en français, court et utile. Base-toi uniquement sur le contexte fourni '
            .'(menu, panier, commande active). Si une info manque, dis-le. '
            ."Ne invente pas de plats absents du menu.\n\nContexte JSON:\n"
            .json_encode($context, JSON_UNESCAPED_UNICODE);

        $messages = [['role' => 'system', 'content' => $system]];

        foreach (array_slice($history, -8) as $turn) {
            if (! in_array($turn['role'] ?? '', ['user', 'assistant'], true)) {
                continue;
            }
            $messages[] = [
                'role' => $turn['role'],
                'content' => Str::limit((string) $turn['content'], 800),
            ];
        }

        $messages[] = ['role' => 'user', 'content' => Str::limit($message, 1000)];

        $response = Http::withToken($key)
            ->timeout(20)
            ->acceptJson()
            ->post($base.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.4,
                'max_tokens' => 450,
                'messages' => $messages,
            ]);

        $response->throw();

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Réponse OpenAI vide.');
        }

        return trim($content);
    }
}
