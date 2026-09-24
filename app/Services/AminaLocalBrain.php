<?php

namespace App\Services;

/**
 * Moteur conversationnel gratuit (sans API payante).
 * Plus « autonome » : anti-répétition, goûts appris, rotation des formulations.
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
        $memory = $this->memoryFromHistory($history, $message, $context);
        $english = $memory['english'];
        $short = $memory['short'];
        $spicy = $memory['spicy'];
        $turn = $this->userTurnCount($history);
        $alreadySaid = $this->mentionedDishesFromHistory($history);

        if ($this->asksLanguageSwitch($lower)) {
            $english = $this->wantsEnglishExplicit($lower);

            return $this->pick($english, $turn, [
                $short ? 'Got it. English from now on. Craving?' : 'Sure. I’ll stay in English. Budget or craving?',
                $short ? 'Switched to English. What sounds good?' : 'Done. I’ll answer in English. Tell me what you want to eat.',
            ], [
                $short ? 'OK, on reste en français. Envies ?' : 'Parfait, je reste en français. Budget ou envie ?',
                $short ? 'Français noté. Tu as faim de quoi ?' : 'C’est noté. Dis-moi ce qui te tente et je pioche dans le vrai menu.',
            ]);
        }

        // Salut : ne se représente pas à chaque message
        if ($this->isGreeting($lower)) {
            if ($turn > 0) {
                return $this->pick($english, $turn, [
                    $short ? 'Still here. Budget or dish?' : 'Still with you. Want another idea, or a different budget?',
                    $short ? 'Yep?' : 'I’m listening. Spicy, local, cheap… what direction?',
                    'Hey again. Shall I pick something new you haven’t seen yet?',
                ], [
                    $short ? 'Toujours là. Budget ou plat ?' : 'Je suis toujours là. Une autre idée, ou un autre budget ?',
                    $short ? 'Oui ?' : 'Je t’écoute. Épicé, local, pas cher… quelle direction ?',
                    'Rebonjour. Je te sors un truc que je ne t’ai pas encore proposé ?',
                ]);
            }

            return $english
                ? ($short
                    ? "Hey, I’m {$agentName}. Budget or craving?"
                    : "Hey! I’m {$agentName}. Give me a budget or a craving and I’ll pick real dishes from open restaurants.")
                : ($short
                    ? "Salut, moi c’est {$agentName}. Budget ou envie ?"
                    : "Salut ! Je suis {$agentName}. Donne un budget ou une envie, je choisis des plats réels parmi les restos ouverts.");
        }

        if ($this->wantsWait($lower) || $this->asksAboutOrder($lower)) {
            return $this->maybeShorten($this->replyWait($context, $english), $short);
        }

        if ($this->asksCart($lower)) {
            return $this->maybeShorten($this->replyCart($context, $english), $short);
        }

        // Lieu / proximité (Ambam, restos proches…) — avant le fallback budget
        if ($this->asksNearby($lower) || $this->asksOpenRestaurants($lower) || isset($context['stated_place'])) {
            return $this->replyNearby(
                $context,
                $english,
                $short,
                $spicy,
                $memory['local'],
                $memory['hearty'],
                $memory['budget'],
                $alreadySaid,
                $turn,
            );
        }

        if ($this->asksBilingual($lower) && ! $this->asksLanguageSwitch($lower)) {
            return $english
                ? 'Yes, French or English works. What are you in the mood for?'
                : 'Oui, français ou anglais. Tu as faim de quoi ?';
        }

        if ($this->isThanks($lower)) {
            return $this->pick($english, $turn, [
                $short ? 'Anytime!' : 'Glad it helped. Want a different plate or a closer spot?',
                $short ? 'You got it.' : 'Cool. Say if you want lighter, spicier, or cheaper next.',
            ], [
                $short ? 'Avec plaisir !' : 'Content que ça aide. Tu veux un autre plat ou un resto plus proche ?',
                $short ? 'Nickel.' : 'Parfait. Dis-moi si tu veux plus léger, plus pimenté ou moins cher.',
            ]);
        }

        if ($this->isSmallTalk($lower)) {
            return $this->pick($english, $turn, [
                $short ? 'Good. Hungry?' : 'I’m good. You hungry? Give a budget and I’ll move.',
                'All good. What should we eat?',
            ], [
                $short ? 'Ça va. Tu as faim ?' : 'Ça va bien. Tu as faim ? Donne un budget et on avance.',
                'Tranquille. On mange quoi ?',
            ]);
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
            || $this->asksAnother($lower);

        // Si l’utilisateur demande « autre chose », forcer une nouvelle reco
        if ($this->asksAnother($lower)) {
            $wantsRecommend = true;
        }

        if ($wantsRecommend && ! $this->isNoise($lower)) {
            return $this->replyRecommend(
                $context,
                $budget,
                $wantsLocal,
                $wantsHearty,
                $spicy,
                $english,
                $short,
                $alreadySaid,
                $turn,
                $memory['aversions'],
                $memory['allergies'],
            );
        }

        // Pose une question utile au lieu du même fallback
        return $this->pick($english, $turn, [
            $short ? 'Budget (FCFA) or craving?' : 'I need one clue: budget in FCFA, or a craving (local, spicy, filling)?',
            $short ? 'How much can you spend?' : 'Want me to pick from what’s open near you, or stay on one restaurant?',
            'Say a max budget and I’ll filter the real menu.',
        ], [
            $short ? 'Budget (FCFA) ou envie ?' : 'Il me faut un indice : budget en FCFA, ou une envie (local, épicé, copieux) ?',
            $short ? 'Tu peux mettre combien ?' : 'Je pioche parmi les restos ouverts, ou on reste sur un resto précis ?',
            'Donne un budget max et je filtre le vrai menu.',
        ]);
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $context
     * @return array{budget: ?int, local: bool, hearty: bool, spicy: bool, short: bool, english: bool, aversions: list<string>, allergies: list<string>}
     */
    private function memoryFromHistory(array $history, string $current, array $context): array
    {
        $tastes = is_array($context['learned_tastes'] ?? null) ? $context['learned_tastes'] : [];
        $budget = $this->extractBudget($current);
        if ($budget === null && isset($tastes['budget_moyen'])) {
            $budget = (int) $tastes['budget_moyen'];
        }

        $currentLower = mb_strtolower($current, 'UTF-8');
        $local = $this->wantsLocal($currentLower) || $this->tasteFlag($tastes, ['local', 'plats_preferes'], 'local');
        $hearty = $this->wantsHearty($currentLower);
        $spicy = $this->wantsSpicy($currentLower) || $this->tasteFlag($tastes, ['piment'], 'oui|fort|moyen|épic');
        $short = $this->wantsShort($currentLower);
        $english = $this->looksEnglish($currentLower);
        $langLocked = false;
        $aversions = $this->splitTasteList((string) ($tastes['aversions'] ?? ''));
        $allergies = $this->splitTasteList((string) ($tastes['allergies'] ?? ''));

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
            'aversions' => $aversions,
            'allergies' => $allergies,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>  $alreadySaid
     * @param  list<string>  $aversions
     * @param  list<string>  $allergies
     */
    private function replyRecommend(
        array $context,
        ?int $budget,
        bool $wantsLocal,
        bool $wantsHearty,
        bool $wantsSpicy,
        bool $english,
        bool $short,
        array $alreadySaid,
        int $turn,
        array $aversions = [],
        array $allergies = [],
    ): string {
        $restaurant = $context['restaurant'] ?? null;
        $menu = $context['menu'] ?? [];
        $banned = array_merge($aversions, $allergies);

        if ($restaurant && $menu !== []) {
            $picks = $this->pickDishes($menu, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, $alreadySaid, $banned, $turn);
            if ($picks === []) {
                // Relâche l’anti-répétition si plus rien
                $picks = $this->pickDishes($menu, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, [], $banned, $turn);
            }
            if ($picks === []) {
                return $english
                    ? "Nothing fits that budget at **{$restaurant['name']}** right now."
                    : "Rien ne rentre dans ce budget chez **{$restaurant['name']}** pour l’instant.";
            }

            $limit = $short ? 2 : 3;
            $lines = [];
            foreach (array_slice($picks, 0, $limit) as $dish) {
                $why = $this->whyDish($dish, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, $english, $turn);
                $lines[] = '- **'.$restaurant['name'].'** - '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*) : '.$why;
            }

            $intro = $this->recoIntro($english, $short, $budget, (string) $restaurant['name'], $turn, $alreadySaid !== []);

            $body = $intro."\n\n".implode("\n\n", $lines);
            if ($short) {
                return $body;
            }

            return $body."\n\n".$this->recoOutro($english, $turn);
        }

        // RAG global : available_dishes puis catalog
        $global = $context['available_dishes'] ?? [];
        if (is_array($global) && $global !== []) {
            $picks = $this->pickDishes($global, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, $alreadySaid, $banned, $turn);
            if ($picks === []) {
                $picks = $this->pickDishes($global, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, [], $banned, $turn);
            }
            if ($picks !== []) {
                $limit = $short ? 2 : 3;
                $lines = [];
                foreach (array_slice($picks, 0, $limit) as $dish) {
                    $restoName = (string) ($dish['restaurant'] ?? 'Resto');
                    $why = $this->whyDish($dish, $budget, $wantsLocal, $wantsHearty, $wantsSpicy, $english, $turn);
                    $lines[] = '- **'.$restoName.'** - '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*) : '.$why;
                }
                $intro = $this->recoIntro($english, $short, $budget, null, $turn, $alreadySaid !== []);

                return $intro."\n\n".implode("\n\n", $lines).($short ? '' : "\n\n".$this->recoOutro($english, $turn));
            }
        }

        if (($context['catalog'] ?? []) === []) {
            return $english
                ? 'No open restaurants with a menu right now.'
                : 'Aucun resto ouvert avec un menu pour l’instant.';
        }

        return $this->replyCatalog($context, $english, $short, $wantsSpicy, $wantsLocal, $wantsHearty, $budget, $alreadySaid, $turn);
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>  $alreadySaid
     */
    private function replyNearby(
        array $context,
        bool $english,
        bool $short,
        bool $spicy,
        bool $local,
        bool $hearty,
        ?int $budget,
        array $alreadySaid,
        int $turn,
    ): string {
        $place = $context['stated_place'] ?? null;
        $catalog = $context['catalog'] ?? [];

        if ($catalog === []) {
            if ($place) {
                return $english
                    ? "I don’t have an open restaurant listed near **{$place}** right now. Try another area (Yaoundé, Douala…) or check back soon."
                    : "Je n’ai pas encore de resto ouvert listé vers **{$place}**. Essaie une autre zone (Yaoundé, Douala…) ou reviens un peu plus tard.";
            }

            return $english
                ? 'Nothing open with delivery right now.'
                : 'Rien d’ouvert en livraison pour le moment.';
        }

        $hasNearby = collect($catalog)->contains(
            fn ($r) => isset($r['distance_km']) && (float) $r['distance_km'] <= 25
        );

        if ($place && ! $hasNearby) {
            $intro = $english
                ? "You’re around **{$place}**. I don’t have a close open spot there yet — here are open restaurants on SynoriaEats (distances may be far):"
                : "Tu es vers **{$place}**. Je n’ai pas encore de resto vraiment proche ouvert là-bas — voici ce qui est ouvert sur SynoriaEats (ça peut être loin) :";
        } elseif ($place) {
            $intro = $english
                ? ($short ? "Near **{$place}**:" : "Got it — you’re around **{$place}**. Closest open picks:")
                : ($short ? "Vers **{$place}** :" : "Compris, tu es vers **{$place}**. Voici ce qui est ouvert / proche :");
        } else {
            $intro = $english
                ? ($short ? 'Nearby / open:' : 'Here are open restaurants near you:')
                : ($short ? 'Proches / ouverts :' : 'Voici les restos ouverts près de toi :');
        }

        $limit = $short ? 3 : 5;
        $lines = [];
        foreach (array_slice($catalog, 0, $limit) as $resto) {
            $samples = $resto['sample_dishes'] ?? [];
            $fit = $this->pickDishes($samples, $budget, $local, $hearty, $spicy, $alreadySaid, [], $turn);
            $dish = $fit[0] ?? ($samples[0] ?? null);
            $dist = isset($resto['distance_km']) ? round((float) $resto['distance_km'], 1).' km' : null;
            $fee = $this->money((int) ($resto['delivery_fee'] ?? 0));
            $meta = $dist
                ? ($english ? "{$dist} · delivery {$fee}" : "{$dist} · livraison {$fee}")
                : ($english ? "delivery {$fee}" : "livraison {$fee}");

            if ($dish) {
                $lines[] = '- **'.$resto['name'].'** - '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*) · '.$meta;
            } else {
                $lines[] = '- **'.$resto['name'].'** · '.$meta;
            }
        }

        $outro = $short
            ? ''
            : ($english
                ? "\n\nGive a budget in FCFA if you want me to filter dishes."
                : "\n\nDonne un budget en FCFA si tu veux que je filtre les plats.");

        return $intro."\n\n".implode("\n\n", $lines).$outro;
    }

    private function asksNearby(string $lower): bool
    {
        return (bool) preg_match('/\b(proche|proches|près|pres|nearby|near me|autour|à côté|a cote|quartier|livrable|pas loin)\b/u', $lower)
            || str_contains($lower, 'près de')
            || str_contains($lower, 'pres de')
            || str_contains($lower, 'restau')
            || str_contains($lower, 'resto');
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<string>  $alreadySaid
     */
    private function replyCatalog(
        array $context,
        bool $english,
        bool $short = false,
        bool $spicy = false,
        bool $local = false,
        bool $hearty = false,
        ?int $budget = null,
        array $alreadySaid = [],
        int $turn = 0,
    ): string {
        $catalog = $context['catalog'] ?? [];
        if ($catalog === []) {
            return $english
                ? 'Nothing open with a menu right now.'
                : 'Rien d’ouvert avec un menu pour le moment.';
        }

        // Rotation du catalogue pour éviter toujours les mêmes 5 premiers
        $offset = $turn % max(1, count($catalog));
        $rotated = array_values(array_merge(
            array_slice($catalog, $offset),
            array_slice($catalog, 0, $offset)
        ));

        $limit = $short ? 3 : 5;
        $lines = [];
        foreach (array_slice($rotated, 0, $limit) as $resto) {
            $samples = $resto['sample_dishes'] ?? [];
            $fit = $this->pickDishes($samples, $budget, $local, $hearty, $spicy, $alreadySaid, [], $turn);
            $dish = $fit[0] ?? ($samples[0] ?? null);
            $fee = $this->money((int) ($resto['delivery_fee'] ?? 0));

            if ($dish) {
                $lines[] = '- **'.$resto['name'].'** - '.$dish['name'].' (*'.$this->money((int) $dish['price']).'*)'
                    .($english ? " · delivery {$fee}" : " · livraison {$fee}");
            } else {
                $lines[] = '- **'.$resto['name'].'** - '
                    .($english
                        ? "{$resto['prep_time_min']}-{$resto['prep_time_max']} min (*delivery {$fee}*)"
                        : "{$resto['prep_time_min']}-{$resto['prep_time_max']} min (*livraison {$fee}*)");
            }
        }

        $intro = $this->pick($english, $turn, [
            $short ? 'Open now:' : 'Here’s a fresh batch of open spots:',
            $short ? 'Other options:' : 'If we rotate a bit, I’d look at:',
            $short ? 'Near you / open:' : 'Different angle, still open and real:',
        ], [
            $short ? 'Ouverts :' : 'Voici une sélection fraîche de restos ouverts :',
            $short ? 'Autres pistes :' : 'Si on change un peu, je regarderais :',
            $short ? 'Ouverts près de toi :' : 'Autre angle, toujours du vrai menu ouvert :',
        ]);

        return $intro."\n\n".implode("\n\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $dishes
     * @param  list<string>  $alreadySaid
     * @param  list<string>  $banned
     * @return list<array<string, mixed>>
     */
    private function pickDishes(
        array $dishes,
        ?int $budget,
        bool $local,
        bool $hearty,
        bool $spicy = false,
        array $alreadySaid = [],
        array $banned = [],
        int $turn = 0,
    ): array {
        $scored = [];
        foreach ($dishes as $index => $dish) {
            $price = (int) ($dish['price'] ?? 0);
            $name = (string) ($dish['name'] ?? '');
            $nameLower = mb_strtolower($name, 'UTF-8');
            $desc = mb_strtolower((string) ($dish['description'] ?? ''), 'UTF-8');
            $blob = $nameLower.' '.$desc;

            if ($budget !== null && $price > $budget) {
                continue;
            }
            if ($this->matchesAny($blob, $banned)) {
                continue;
            }

            $score = 10 + ($index % 3); // léger bruit pour casser l’ordre fixe
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
            // Fortement pénaliser les plats déjà proposés
            if ($this->matchesAny($nameLower, $alreadySaid)) {
                $score -= 40;
            }
            // Rotation douce selon le tour de conversation
            $score += (($turn + $index) % 5);

            $scored[] = ['dish' => $dish, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Ne garder que score raisonnable (évite de renvoyer toujours les mêmes malgré pénalité)
        $filtered = array_values(array_filter($scored, fn ($row) => $row['score'] > -20));

        return array_map(fn ($row) => $row['dish'], $filtered !== [] ? $filtered : $scored);
    }

    /**
     * @param  array<string, mixed>  $dish
     */
    private function whyDish(array $dish, ?int $budget, bool $local, bool $hearty, bool $spicy, bool $english, int $turn = 0): string
    {
        $price = (int) ($dish['price'] ?? 0);
        $name = mb_strtolower((string) ($dish['name'] ?? ''), 'UTF-8');
        $desc = mb_strtolower((string) ($dish['description'] ?? ''), 'UTF-8');
        $blob = $name.' '.$desc;

        $reasonsEn = [];
        $reasonsFr = [];
        if ($spicy && $this->looksSpicy($blob)) {
            $reasonsEn[] = 'hits your spicy craving';
            $reasonsFr[] = 'ça colle à ton envie d’épicé';
        }
        if ($budget !== null && $price <= $budget) {
            $reasonsEn[] = 'stays under '.$this->money($budget);
            $reasonsFr[] = 'reste sous '.$this->money($budget);
        }
        if ($local && $this->looksLocal($name)) {
            $reasonsEn[] = 'classic Cameroon comfort';
            $reasonsFr[] = 'un classique local qui console';
        }
        if ($hearty && $this->looksHearty($name)) {
            $reasonsEn[] = 'filling when you’re starving';
            $reasonsFr[] = 'copieux quand la faim est sérieuse';
        }
        if ($reasonsEn === []) {
            $reasonsEn = ['solid pick on the live menu', 'actually available right now', 'worth a try tonight'];
            $reasonsFr = ['bon choix sur le menu actuel', 'disponible maintenant', 'vaut le détour ce soir'];
        }

        $pool = $english ? $reasonsEn : $reasonsFr;

        return $pool[$turn % count($pool)];
    }

    private function recoIntro(bool $english, bool $short, ?int $budget, ?string $restaurant, int $turn, bool $avoidingRepeat): string
    {
        $money = $budget ? $this->money($budget) : null;
        if ($english) {
            if ($short) {
                return $money ? "Around {$money}:" : ($avoidingRepeat ? 'Fresh picks:' : 'My picks:');
            }
            $options = $restaurant
                ? [
                    $money ? "You said about {$money}. At **{$restaurant}**, this time I’d try:" : "At **{$restaurant}**, here’s a different cut:",
                    $money ? "Staying near {$money} at **{$restaurant}**:" : "Still at **{$restaurant}**, new angles:",
                    $avoidingRepeat
                        ? "Skipping what I already showed. At **{$restaurant}**:"
                        : "At **{$restaurant}**, I’d go with:",
                ]
                : [
                    $money ? "Around {$money}, from what’s open:" : 'From the live catalog:',
                    $money ? "Filtering under {$money}:" : ($avoidingRepeat ? 'Something you haven’t seen yet:' : 'Here’s what fits:'),
                    'Real dishes only, no invention:',
                ];

            return $options[$turn % count($options)];
        }

        if ($short) {
            return $money ? "Vers {$money} :" : ($avoidingRepeat ? 'Nouveaux choix :' : 'Mes choix :');
        }

        $options = $restaurant
            ? [
                $money ? "Tu as parlé d’environ {$money}. Chez **{$restaurant}**, cette fois :" : "Chez **{$restaurant}**, autre sélection :",
                $money ? "Toujours autour de {$money} chez **{$restaurant}** :" : "Toujours chez **{$restaurant}**, nouveaux angles :",
                $avoidingRepeat
                    ? "J’évite ce que je t’ai déjà sorti. Chez **{$restaurant}** :"
                    : "Chez **{$restaurant}**, je te penche vers :",
            ]
            : [
                $money ? "Vers {$money}, parmi ce qui est ouvert :" : 'Dans le catalogue ouvert :',
                $money ? "Filtré sous {$money} :" : ($avoidingRepeat ? 'Un truc que tu n’as pas encore vu :' : 'Voici ce qui colle :'),
                'Uniquement des plats réels, rien d’inventé :',
            ];

        return $options[$turn % count($options)];
    }

    private function recoOutro(bool $english, int $turn): string
    {
        return $this->pick($english, $turn, [
            'Want spicier, lighter, or shall we check your cart?',
            'Say “another one” if you want a different plate.',
            'I can narrow by distance or drop the price further.',
        ], [
            'Tu veux plus épicé, plus léger, ou on regarde ton panier ?',
            'Dis « autre chose » si tu veux un plat différent.',
            'Je peux resserrer sur la distance ou baisser encore le budget.',
        ]);
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

    /**
     * @param  list<string>  $en
     * @param  list<string>  $fr
     */
    private function pick(bool $english, int $turn, array $en, array $fr): string
    {
        $pool = $english ? $en : $fr;

        return $pool[$turn % count($pool)];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    private function userTurnCount(array $history): int
    {
        $n = 0;
        foreach ($history as $turn) {
            if (($turn['role'] ?? '') === 'user') {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Plats déjà cités par Sara (anti-répétition).
     *
     * @param  list<array{role: string, content: string}>  $history
     * @return list<string>
     */
    private function mentionedDishesFromHistory(array $history): array
    {
        $names = [];
        foreach ($history as $turn) {
            if (($turn['role'] ?? '') !== 'assistant') {
                continue;
            }
            $content = (string) ($turn['content'] ?? '');
            if (preg_match_all('/\*\*[^*]+\*\*\s*[-—]\s*([^*\n(]+)/u', $content, $m)) {
                foreach ($m[1] as $dish) {
                    $names[] = mb_strtolower(trim($dish), 'UTF-8');
                }
            }
        }

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * @param  list<string>  $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = trim(mb_strtolower((string) $needle, 'UTF-8'));
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function splitTasteList(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[,;\/|]+/u', $raw) ?: [];

        return array_values(array_filter(array_map(
            static fn ($p) => trim(mb_strtolower((string) $p, 'UTF-8')),
            $parts
        )));
    }

    /**
     * @param  array<string, mixed>  $tastes
     * @param  list<string>  $keys
     */
    private function tasteFlag(array $tastes, array $keys, string $pattern): bool
    {
        foreach ($keys as $key) {
            $val = mb_strtolower((string) ($tastes[$key] ?? ''), 'UTF-8');
            if ($val !== '' && preg_match('/'.$pattern.'/u', $val)) {
                return true;
            }
        }

        return false;
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

    private function asksAnother(string $lower): bool
    {
        return (bool) preg_match('/\b(autre chose|autre plat|autre idée|autre idee|something else|another|différent|different|change)\b/u', $lower);
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
            || (str_contains($lower, 'english') && str_contains($lower, 'french'))
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
        return (bool) preg_match('/\b(merci|thanks|thank you|d[\'’]?acc+ord)\b/u', $lower)
            || $lower === 'ok'
            || $lower === 'okay';
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
