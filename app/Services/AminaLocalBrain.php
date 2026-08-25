<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Moteur conversationnel gratuit (sans API payante).
 * Utilise l’historique + le menu réel pour conseiller comme Amina.
 */
class AminaLocalBrain
{
    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, content: string}>  $history
     */
    public function reply(string $message, array $context, array $history, string $agentName): string
    {
        $message = trim($message);
        $lower = mb_strtolower($message, 'UTF-8');
        $memory = $this->memoryFromHistory($history, $message);
        $english = $this->looksEnglish($lower);

        if ($this->isGreeting($lower)) {
            return $english
                ? "Hey! I’m {$agentName}. Tell me your budget or what you’re craving and I’ll guide you."
                : "Hééé salut ! Moi c’est {$agentName}. Dis-moi ton budget ou ce que tu as envie de manger, on trouve ça ensemble.";
        }

        if ($this->wantsWait($lower) || $this->asksAboutOrder($lower)) {
            return $this->replyWait($context, $english);
        }

        if ($this->asksCart($lower)) {
            return $this->replyCart($context, $english);
        }

        if ($this->asksOpenRestaurants($lower)) {
            return $this->replyCatalog($context, $english);
        }

        if ($this->asksBilingual($lower)) {
            return $english
                ? 'Yes, I can chat in English or French. What are you in the mood for?'
                : 'Oui je suis bilingue ! Parle-moi en français ou en anglais, je suis avec toi. Tu as faim de quoi ?';
        }

        if ($this->isThanks($lower) || $this->isSmallTalk($lower)) {
            return $english
                ? 'Anytime! I’m here if you want another dish or a restaurant closer to you.'
                : 'Avec plaisir ! Je suis là si tu veux un autre plat ou un resto plus proche de toi.';
        }

        $budget = $memory['budget'];
        $wantsLocal = $memory['local'] || $this->wantsLocal($lower);
        $wantsHearty = $memory['hearty'] || $this->wantsHearty($lower);
        $wantsRecommend = $this->wantsRecommend($lower)
            || $this->mentionsFood($lower)
            || $budget !== null
            || $wantsLocal
            || $wantsHearty
            || $this->isHungry($lower);

        // Ne pas relancer une reco si le message courant n’exprime aucune envie
        if ($wantsRecommend && ! $this->isNoise($lower)) {
            return $this->replyRecommend(
                $context,
                $budget,
                $wantsLocal,
                $wantsHearty,
                $english,
            );
        }

        return $english
            ? 'Got it. Give me a budget in FCFA or a craving (local, light, filling) and I’ll pick something real from the menu.'
            : 'Ok je t’écoute. Donne-moi un budget en FCFA ou une envie (local, léger, copieux) et je te choisis un vrai plat du menu.';
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{budget: ?int, local: bool, hearty: bool}
     */
    private function memoryFromHistory(array $history, string $current): array
    {
        $budget = $this->extractBudget($current);
        $local = $this->wantsLocal(mb_strtolower($current, 'UTF-8'));
        $hearty = $this->wantsHearty(mb_strtolower($current, 'UTF-8'));

        foreach (array_reverse($history) as $turn) {
            if (($turn['role'] ?? '') !== 'user') {
                continue;
            }
            $text = (string) ($turn['content'] ?? '');
            $budget ??= $this->extractBudget($text);
            $lower = mb_strtolower($text, 'UTF-8');
            $local = $local || $this->wantsLocal($lower);
            $hearty = $hearty || $this->wantsHearty($lower);
        }

        return [
            'budget' => $budget,
            'local' => $local,
            'hearty' => $hearty,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyRecommend(
        array $context,
        ?int $budget,
        bool $wantsLocal,
        bool $wantsHearty,
        bool $english,
    ): string {
        $restaurant = $context['restaurant'] ?? null;
        $menu = $context['menu'] ?? [];
        $catalog = $context['catalog'] ?? [];

        if ($restaurant && $menu !== []) {
            $picks = $this->pickDishes($menu, $budget, $wantsLocal, $wantsHearty);
            if ($picks === []) {
                return $english
                    ? "In {$restaurant['name']}, nothing fits that budget right now. Want me to raise it a bit or check another place?"
                    : "Chez {$restaurant['name']}, rien ne rentre vraiment dans ce budget là. On monte un peu le budget, ou on regarde un autre resto ?";
            }

            $lines = [];
            foreach (array_slice($picks, 0, 3) as $dish) {
                $why = $this->whyDish($dish, $budget, $wantsLocal, $wantsHearty, $english);
                $lines[] = "• {$dish['name']} ({$this->money((int) $dish['price'])}) : {$why}";
            }

            $intro = $budget
                ? ($english
                    ? "You mentioned about {$this->money($budget)}. At {$restaurant['name']}, I’d go with:"
                    : "Tu as parlé d’environ {$this->money($budget)}. Chez {$restaurant['name']}, moi je te penche vers :")
                : ($english
                    ? "At {$restaurant['name']}, here’s what I’d pick for you:"
                    : "Chez {$restaurant['name']}, voilà ce que je te conseille :");

            $outro = $english
                ? 'Want something spicier, lighter, or should I check your cart next?'
                : 'Tu veux plus épicé, plus léger, ou on regarde ton panier ?';

            return $intro."\n".implode("\n", $lines)."\n".$outro;
        }

        if ($catalog === []) {
            return $english
                ? 'No open restaurants with a menu right now. Come back a bit later or browse /restaurants.'
                : 'Aucun resto ouvert avec un menu pour l’instant. Reviens un peu plus tard ou passe sur /restaurants.';
        }

        $suggestions = [];
        foreach (array_slice($catalog, 0, 4) as $resto) {
            $samples = $resto['sample_dishes'] ?? [];
            $fit = $this->pickDishes($samples, $budget, $wantsLocal, $wantsHearty);
            $dish = $fit[0] ?? ($samples[0] ?? null);
            $dist = $resto['distance_km'] ?? null;
            $place = $resto['place_label'] ?? null;
            $distBit = $dist !== null
                ? ($english ? " · {$dist} km" : " · {$dist} km")
                : '';
            $placeBit = $place ? " ({$place})" : '';
            if (! $dish) {
                $suggestions[] = "• {$resto['name']}{$placeBit}{$distBit}";

                continue;
            }
            $suggestions[] = "• {$resto['name']}{$placeBit}{$distBit} → {$dish['name']} ({$this->money((int) $dish['price'])})";
        }

        $budgetBit = $budget
            ? ($english ? "With about {$this->money($budget)}, " : "Avec environ {$this->money($budget)}, ")
            : '';

        $pref = '';
        if ($wantsLocal || $wantsHearty) {
            $pref = $english
                ? 'You asked for something local/filling. '
                : 'Tu voulais du local / copieux. ';
        }

        return ($english
            ? "{$pref}{$budgetBit}I’d start here:"
            : "{$pref}{$budgetBit}je te propose de commencer par là :")
            ."\n".implode("\n", $suggestions)
            ."\n".($english
                ? 'Open a restaurant and I’ll dig into its full menu with you.'
                : 'Ouvre un resto et je plonge dans son menu avec toi.');
    }

    /**
     * @param  list<array<string, mixed>>  $dishes
     * @return list<array<string, mixed>>
     */
    private function pickDishes(array $dishes, ?int $budget, bool $local, bool $hearty): array
    {
        $scored = [];
        foreach ($dishes as $dish) {
            $price = (int) ($dish['price'] ?? 0);
            $name = mb_strtolower((string) ($dish['name'] ?? ''), 'UTF-8');
            $desc = mb_strtolower((string) ($dish['description'] ?? ''), 'UTF-8');
            $blob = $name.' '.$desc;

            if ($budget !== null && $price > $budget) {
                continue;
            }

            $score = 10;
            if ($budget !== null) {
                $score += (int) max(0, 20 - abs($budget - $price) / max(1, $budget / 20));
            }
            if ($local && $this->looksLocal($blob)) {
                $score += 15;
            }
            if ($hearty && $this->looksHearty($blob)) {
                $score += 12;
            }

            $scored[] = ['dish' => $dish, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($row) => $row['dish'], $scored);
    }

    /**
     * @param  array<string, mixed>  $dish
     */
    private function whyDish(array $dish, ?int $budget, bool $local, bool $hearty, bool $english): string
    {
        $price = (int) ($dish['price'] ?? 0);
        $name = mb_strtolower((string) ($dish['name'] ?? ''), 'UTF-8');

        if ($budget !== null && $price <= $budget) {
            return $english
                ? 'fits your budget and still feels like a proper meal'
                : 'ça rentre dans ton budget et ça remplit bien';
        }
        if ($local && $this->looksLocal($name)) {
            return $english ? 'classic local comfort food' : 'un classique local qui console';
        }
        if ($hearty && $this->looksHearty($name)) {
            return $english ? 'filling when you’re really hungry' : 'copieux quand la faim est sérieuse';
        }

        return $english ? 'solid pick on this menu' : 'un bon choix sur ce menu';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyWait(array $context, bool $english): string
    {
        $order = $context['active_order'] ?? null;
        if ($order) {
            $min = (int) ($order['prep_time_min'] ?? 15);
            $max = (int) ($order['prep_time_max'] ?? 30);
            $elapsed = (int) ($order['elapsed_minutes'] ?? 0);

            return $english
                ? "Your order {$order['number']} at {$order['restaurant']} is « {$order['status_label']} ». Prep is usually {$min}-{$max} min. You’ve been waiting about {$elapsed} min. I’m here if you want to chat."
                : "Ta commande {$order['number']} chez {$order['restaurant']} est « {$order['status_label']} ». Préparation souvent {$min}-{$max} min. Ça fait environ {$elapsed} min. Je reste là si tu veux papoter en attendant.";
        }

        $resto = $context['restaurant'] ?? null;
        if ($resto) {
            return $english
                ? "At {$resto['name']}, expect about {$resto['prep_time_min']}-{$resto['prep_time_max']} min once the kitchen starts. Want a dish idea meanwhile?"
                : "Chez {$resto['name']}, compte environ {$resto['prep_time_min']}-{$resto['prep_time_max']} min une fois en cuisine. Tu veux une idée de plat en attendant ?";
        }

        return $english
            ? 'No active order yet. Open a restaurant, order, and I’ll keep you company while it cooks.'
            : 'Pas encore de commande active. Ouvre un resto, commande, et je te tiens compagnie pendant que ça mijote.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyCart(array $context, bool $english): string
    {
        $cart = $context['cart'] ?? ['count' => 0, 'subtotal' => 0, 'lines' => []];
        if (($cart['count'] ?? 0) < 1) {
            return $english
                ? 'Your cart is empty. Tell me a budget and I’ll suggest something to add.'
                : 'Ton panier est vide. Donne-moi un budget et je te suggère quoi ajouter.';
        }

        $lines = [];
        foreach ($cart['lines'] as $line) {
            $lines[] = "• {$line['name']} x{$line['quantity']} ({$this->money((int) $line['unit_price'])})";
        }

        return ($english ? 'In your cart:' : 'Dans ton panier :')
            ."\n".implode("\n", $lines)
            ."\n".($english
                ? 'Subtotal '.$this->money((int) $cart['subtotal']).'. Want a drink or a side?'
                : 'Sous-total '.$this->money((int) $cart['subtotal']).'. Tu ajoutes une boisson ou un accompagnement ?');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyCatalog(array $context, bool $english): string
    {
        $catalog = $context['catalog'] ?? [];
        if ($catalog === []) {
            return $english
                ? 'Nothing open with a menu right now.'
                : 'Rien d’ouvert avec un menu pour le moment.';
        }

        $lines = [];
        foreach (array_slice($catalog, 0, 6) as $r) {
            $lines[] = $english
                ? "• {$r['name']} · {$r['prep_time_min']}-{$r['prep_time_max']} min · fee {$this->money((int) $r['delivery_fee'])}"
                : "• {$r['name']} · {$r['prep_time_min']}-{$r['prep_time_max']} min · frais {$this->money((int) $r['delivery_fee'])}";
        }

        return ($english ? 'Open now:' : 'Ouverts maintenant :')."\n".implode("\n", $lines);
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

    private function isGreeting(string $lower): bool
    {
        return (bool) preg_match('/\b(hi|hey|hello|yo|wassup|salut|bonjour|bonsoir|coucou|slt)\b/u', $lower)
            || str_contains($lower, 'tu parle')
            || str_contains($lower, 'tu me parle');
    }

    private function wantsWait(string $lower): bool
    {
        return str_contains($lower, 'attente')
            || str_contains($lower, 'combien de temps')
            || str_contains($lower, 'wait time')
            || (str_contains($lower, 'temps') && str_contains($lower, 'commande'));
    }

    private function asksAboutOrder(string $lower): bool
    {
        return str_contains($lower, 'où en est')
            || str_contains($lower, 'ma commande')
            || str_contains($lower, 'order status');
    }

    private function asksCart(string $lower): bool
    {
        return str_contains($lower, 'panier') || str_contains($lower, 'cart');
    }

    private function asksOpenRestaurants(string $lower): bool
    {
        return str_contains($lower, 'ouvert')
            || str_contains($lower, 'restos')
            || str_contains($lower, 'restaurants')
            || str_contains($lower, 'open now');
    }

    private function asksBilingual(string $lower): bool
    {
        return str_contains($lower, 'bilingue')
            || str_contains($lower, 'english')
            || str_contains($lower, 'anglais');
    }

    private function wantsRecommend(string $lower): bool
    {
        return str_contains($lower, 'recommand')
            || str_contains($lower, 'conseil')
            || str_contains($lower, 'suggest')
            || str_contains($lower, 'idée')
            || str_contains($lower, 'idee')
            || str_contains($lower, 'quoi manger')
            || str_contains($lower, 'guide');
    }

    private function isHungry(string $lower): bool
    {
        return str_contains($lower, 'faim')
            || str_contains($lower, 'hungry')
            || str_contains($lower, 'envie')
            || str_contains($lower, 'manger')
            || str_contains($lower, 'veux');
    }

    private function mentionsFood(string $lower): bool
    {
        return (bool) preg_match('/okok|ndole|ndolé|poulet|dg|eru|plantain|riz|sauce|brochette|grill/u', $lower);
    }

    private function wantsLocal(string $lower): bool
    {
        return str_contains($lower, 'local')
            || str_contains($lower, 'cameroun')
            || str_contains($lower, 'africain')
            || str_contains($lower, 'tradition');
    }

    private function wantsHearty(string $lower): bool
    {
        return str_contains($lower, 'copieux')
            || str_contains($lower, 'rempli')
            || str_contains($lower, 'consistant')
            || str_contains($lower, 'filling')
            || str_contains($lower, 'hearty');
    }

    private function looksLocal(string $blob): bool
    {
        return (bool) preg_match('/ndole|ndolé|poulet\s*dg|eru|achu|okok|kondre|koki|plantain|foufou|garri|sauce\s*jaune|bongo|mbongo/u', $blob);
    }

    private function looksHearty(string $blob): bool
    {
        return (bool) preg_match('/poulet|riz|plantain|foufou|brochette|grill|sauce|dg|eru|ndole|ndolé/u', $blob);
    }

    private function isThanks(string $lower): bool
    {
        return (bool) preg_match('/\b(merci|thanks|thank you|d[\'’]?acc+ord|ok|okay)\b/u', $lower);
    }

    private function isNoise(string $lower): bool
    {
        $clean = preg_replace('/[^a-zàâäéèêëïîôùûüç0-9\s]/u', '', $lower) ?? $lower;

        return mb_strlen(trim($clean)) < 3
            || $this->isThanks($clean)
            || $this->isSmallTalk($clean);
    }

    private function isSmallTalk(string $lower): bool
    {
        return str_contains($lower, 'comment ça va')
            || str_contains($lower, 'ça va')
            || str_contains($lower, 'cva')
            || str_contains($lower, 'cv ')
            || $lower === 'cv'
            || str_contains($lower, 'norhh')
            || str_contains($lower, 'ekieu');
    }

    private function looksEnglish(string $lower): bool
    {
        return (bool) preg_match('/\b(what|where|how|please|hungry|budget|recommend|thanks|hello|hey|wassup)\b/u', $lower)
            && ! (bool) preg_match('/\b(je|tu|bonjour|salut|faim|plat)\b/u', $lower);
    }
}
