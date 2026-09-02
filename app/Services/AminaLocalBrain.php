<?php

namespace App\Services;

/**
 * Moteur conversationnel gratuit (sans API payante).
 * Utilise l’historique + le menu réel pour conseiller comme Sara.
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
        $english = $memory['english'];
        $short = $memory['short'];
        $spicy = $memory['spicy'];

        if ($this->asksLanguageSwitch($lower)) {
            $english = $this->wantsEnglishExplicit($lower);
            if ($english) {
                return $short
                    ? 'Got it. I’ll reply in English. What are you craving?'
                    : 'Sure. I’ll keep answering in English from now on. Tell me a budget or a craving and I’ll guide you.';
            }

            return $short
                ? 'OK, on reste en français. Tu as faim de quoi ?'
                : 'Parfait, je te réponds en français. Dis-moi ton budget ou une envie et on trouve ça.';
        }

        if ($this->isGreeting($lower)) {
            return $english
                ? ($short
                    ? "Hey, I’m {$agentName}. Budget or craving?"
                    : "Hey! I’m {$agentName}, your SynoriaEats advisor. Tell me a budget or what you want to eat and I’ll pick real dishes nearby.")
                : ($short
                    ? "Salut, moi c’est {$agentName}. Budget ou envie ?"
                    : "Salut ! Je suis {$agentName}, ta conseillère SynoriaEats. Dis-moi ton budget ou ce que tu veux manger, je te guide avec de vrais plats.");
        }

        if ($this->wantsWait($lower) || $this->asksAboutOrder($lower)) {
            return $this->maybeShorten($this->replyWait($context, $english), $short);
        }

        if ($this->asksCart($lower)) {
            return $this->maybeShorten($this->replyCart($context, $english), $short);
        }

        if ($this->asksOpenRestaurants($lower)) {
            return $this->replyCatalog($context, $english, $short, $spicy, $memory['local'], $memory['hearty'], $memory['budget']);
        }

        if ($this->asksBilingual($lower) && ! $this->asksLanguageSwitch($lower)) {
            return $english
                ? 'Yes, French or English works. What are you in the mood for?'
                : 'Oui, français ou anglais. Tu as faim de quoi ?';
        }

        if ($this->isThanks($lower) || $this->isSmallTalk($lower)) {
            return $english
                ? ($short ? 'Anytime!' : 'Anytime! Ask if you want another dish or a closer restaurant.')
                : ($short ? 'Avec plaisir !' : 'Avec plaisir ! Dis-moi si tu veux un autre plat ou un resto plus proche.');
        }

        $budget = $memory['budget'];
        $wantsLocal = $memory['local'];
        $wantsHearty = $memory['hearty'];
        $wantsRecommend = $this->wantsRecommend($lower)
            || $this->mentionsFood($lower)
            || $budget !== null
            || $wantsLocal
            || $wantsHearty
            || $spicy
            || $this->isHungry($lower)
            || $short; // "fais court" after a topic often still wants a reco

        if ($wantsRecommend && ! $this->isNoise($lower)) {
            return $this->replyRecommend(
                $context,
                $budget,
                $wantsLocal,
                $wantsHearty,
                $spicy,
                $english,
                $short,
            );
        }

        return $english
            ? ($short
                ? 'Give a budget (FCFA) or a craving.'
                : 'Got it. Give me a budget in FCFA or a craving (local, spicy, filling) and I’ll pick something real.')
            : ($short
                ? 'Donne un budget (FCFA) ou une envie.'
                : 'Ok. Donne-moi un budget en FCFA ou une envie (local, épicé, copieux) et je te choisis un vrai plat.');
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{budget: ?int, local: bool, hearty: bool, spicy: bool, short: bool, english: bool}
     */
    private function memoryFromHistory(array $history, string $current): array
    {
        $budget = $this->extractBudget($current);
        $currentLower = mb_strtolower($current, 'UTF-8');
        $local = $this->wantsLocal($currentLower);
        $hearty = $this->wantsHearty($currentLower);
        $spicy = $this->wantsSpicy($currentLower);
        $short = $this->wantsShort($currentLower);
        $english = $this->looksEnglish($currentLower);
        $langLocked = false;

        foreach (array_reverse($history) as $turn) {
            if (($turn['role'] ?? '') !== 'user') {
                continue;
            }
            $text = (string) ($turn['content'] ?? '');
            $lower = mb_strtolower($text, 'UTF-8');
            $budget ??= $this->extractBudget($text);
            $local = $local || $this->wantsLocal($lower);
            $hearty = $hearty || $this->wantsHearty($lower);
            $spicy = $spicy || $this->wantsSpicy($lower);
            $short = $short || $this->wantsShort($lower);

            if (! $langLocked && $this->asksLanguageSwitch($lower)) {
                $english = $this->wantsEnglishExplicit($lower);
                $langLocked = true;
            } elseif (! $langLocked && $this->looksEnglish($lower)) {
                $english = true;
            }
        }

        if ($this->asksLanguageSwitch($currentLower)) {
            $english = $this->wantsEnglishExplicit($currentLower);
        }

        return [
            'budget' => $budget,
            'local' => $local,
            'hearty' => $hearty,
            'spicy' => $spicy,
            'short' => $short,
            'english' => $english,
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
        bool $wantsSpicy,
        bool $english,
        bool $short,
    ): string {
        $restaurant = $context['restaurant'] ?? null;
        $menu = $context['menu'] ?? [];
        $catalog = $context['catalog'] ?? [];

        if ($restaurant && $menu !== []) {
            $picks = $this->pickDishes($menu, $budget, $wantsLocal, $wantsHearty, $wantsSpicy);
            if ($picks === []) {
                return $english
                    ? "Nothing fits that budget at **{$restaurant['name']}** right now."
                    : "Rien ne rentre dans ce budget chez **{$restaurant['name']}** pour l’instant.";
            }

            $limit = $short ? 2 : 3;
            $lines = [];
            foreach (array_slice($picks, 0, $limit) as $dish) {
                $why = $this->whyDish($dish, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, $english);
                $lines[] = '- **'.$restaurant['name'].'** — '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*) : '.$why;
            }

            $intro = $budget
                ? ($english
                    ? ($short
                        ? "Around {$this->money($budget)}:"
                        : "You mentioned about {$this->money($budget)}. At **{$restaurant['name']}**, I’d go with:")
                    : ($short
                        ? "Vers {$this->money($budget)} :"
                        : "Tu as parlé d’environ {$this->money($budget)}. Chez **{$restaurant['name']}**, je te penche vers :"))
                : ($english
                    ? ($short ? 'My picks:' : "At **{$restaurant['name']}**, I’d go with:")
                    : ($short ? 'Mes choix :' : "Chez **{$restaurant['name']}**, je te penche vers :"));

            $body = $intro."\n\n".implode("\n\n", $lines);

            if ($short) {
                return $body;
            }

            $outro = $english
                ? 'Want spicier, lighter, or shall we check your cart?'
                : 'Tu veux plus épicé, plus léger, ou on regarde ton panier ?';

            return $body."\n\n".$outro;
        }

        if ($catalog === []) {
            return $english
                ? 'No open restaurants with a menu right now.'
                : 'Aucun resto ouvert avec un menu pour l’instant.';
        }

        return $this->replyCatalog($context, $english, $short, $wantsSpicy, $wantsLocal, $wantsHearty, $budget);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyCatalog(
        array $context,
        bool $english,
        bool $short = false,
        bool $spicy = false,
        bool $local = false,
        bool $hearty = false,
        ?int $budget = null,
    ): string {
        $catalog = $context['catalog'] ?? [];
        if ($catalog === []) {
            return $english
                ? 'Nothing open with a menu right now.'
                : 'Rien d’ouvert avec un menu pour le moment.';
        }

        $limit = $short ? 3 : 5;
        $lines = [];
        foreach (array_slice($catalog, 0, $limit) as $resto) {
            $samples = $resto['sample_dishes'] ?? [];
            $fit = $this->pickDishes($samples, $budget, $local, $hearty, $spicy);
            $dish = $fit[0] ?? ($samples[0] ?? null);
            $fee = $this->money((int) ($resto['delivery_fee'] ?? 0));

            if ($dish) {
                $lines[] = '- **'.$resto['name'].'** — '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*)'
                    .($english ? " · delivery {$fee}" : " · livraison {$fee}");
            } else {
                $lines[] = '- **'.$resto['name'].'** — '
                    .($english
                        ? "{$resto['prep_time_min']}-{$resto['prep_time_max']} min (*delivery {$fee}*)"
                        : "{$resto['prep_time_min']}-{$resto['prep_time_max']} min (*livraison {$fee}*)");
            }
        }

        $intro = $english ? ($short ? 'Open now:' : 'Here’s what I’d start with:') : ($short ? 'Ouverts :' : 'Voici par où je commencerais :');

        return $intro."\n\n".implode("\n\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $dishes
     * @return list<array<string, mixed>>
     */
    private function pickDishes(array $dishes, ?int $budget, bool $local, bool $hearty, bool $spicy = false): array
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
            if ($spicy && $this->looksSpicy($blob)) {
                $score += 18;
            }

            $scored[] = ['dish' => $dish, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_map(fn ($row) => $row['dish'], $scored);
    }

    /**
     * @param  array<string, mixed>  $dish
     */
    private function whyDish(array $dish, ?int $budget, bool $local, bool $hearty, bool $spicy, bool $english): string
    {
        $price = (int) ($dish['price'] ?? 0);
        $name = mb_strtolower((string) ($dish['name'] ?? ''), 'UTF-8');
        $desc = mb_strtolower((string) ($dish['description'] ?? ''), 'UTF-8');
        $blob = $name.' '.$desc;

        if ($spicy && $this->looksSpicy($blob)) {
            return $english ? 'matches your spicy craving' : 'ça colle à ton envie d’épicé';
        }
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
                ? "Order **{$order['number']}** at **{$order['restaurant']}** is « {$order['status_label']} ». Usually {$min}-{$max} min. ~{$elapsed} min so far."
                : "Commande **{$order['number']}** chez **{$order['restaurant']}** : « {$order['status_label']} ». Souvent {$min}-{$max} min. ~{$elapsed} min déjà.";
        }

        $resto = $context['restaurant'] ?? null;
        if ($resto) {
            return $english
                ? "At **{$resto['name']}**, about {$resto['prep_time_min']}-{$resto['prep_time_max']} min once cooking starts."
                : "Chez **{$resto['name']}**, compte {$resto['prep_time_min']}-{$resto['prep_time_max']} min une fois en cuisine.";
        }

        return $english
            ? 'No active order yet. Order something and I’ll track it with you.'
            : 'Pas encore de commande active. Commande et je te suis le statut.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function replyCart(array $context, bool $english): string
    {
        $cart = $context['cart'] ?? ['count' => 0, 'subtotal' => 0, 'lines' => []];
        if (($cart['count'] ?? 0) < 1) {
            return $english
                ? 'Cart is empty. Give a budget and I’ll suggest something.'
                : 'Panier vide. Donne un budget et je te suggère quoi ajouter.';
        }

        $lines = [];
        foreach ($cart['lines'] as $line) {
            $lines[] = '- **'.$line['name'].'** x'.$line['quantity'].' (*'.$this->money((int) $line['unit_price']).'*)';
        }

        return ($english ? 'In your cart:' : 'Dans ton panier :')
            ."\n\n".implode("\n\n", $lines)
            ."\n\n".($english
                ? 'Subtotal *'.$this->money((int) $cart['subtotal']).'*'
                : 'Sous-total *'.$this->money((int) $cart['subtotal']).'*');
    }

    private function maybeShorten(string $text, bool $short): string
    {
        if (! $short) {
            return $text;
        }

        $parts = preg_split("/\n\n+/", trim($text)) ?: [$text];

        return implode("\n\n", array_slice($parts, 0, 2));
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

    private function asksLanguageSwitch(string $lower): bool
    {
        return (bool) preg_match('/\b(en anglais|in english|speak english|reply in english|switch to english|en français|in french|speak french|réponds en français|parle (en )?français|parle (en )?anglais)\b/u', $lower);
    }

    private function wantsEnglishExplicit(string $lower): bool
    {
        return (bool) preg_match('/\b(en anglais|in english|speak english|reply in english|switch to english|parle (en )?anglais)\b/u', $lower);
    }

    private function wantsShort(string $lower): bool
    {
        return (bool) preg_match('/\b(court|short|brief|briefly|résumé|resume|concis|tl;dr|fais court|be short)\b/u', $lower);
    }

    private function wantsSpicy(string $lower): bool
    {
        return (bool) preg_match('/\b(épicé|epice|spicy|piment|chili|piquant|hot sauce)\b/u', $lower);
    }

    private function looksSpicy(string $blob): bool
    {
        return (bool) preg_match('/piment|épic|spicy|mbongo|bongo|braisé|braise|chili|piquant/u', $blob);
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
        return (bool) preg_match('/okok|ndole|ndolé|poulet|dg|eru|plantain|riz|sauce|brochette|grill|mbongo|poisson/u', $lower);
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
            || $this->isSmallTalk($clean)
            || $this->asksLanguageSwitch($clean)
            || $this->wantsShort($clean);
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
        if ($this->wantsEnglishExplicit($lower)) {
            return true;
        }

        return (bool) preg_match('/\b(what|where|how|please|hungry|budget|recommend|thanks|hello|hey|wassup|spicy|short)\b/u', $lower)
            && ! (bool) preg_match('/\b(je|tu|bonjour|salut|faim|plat|épicé)\b/u', $lower);
    }
}
