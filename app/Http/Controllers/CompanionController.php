<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Services\CulinaryCompanion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanionController extends Controller
{
    private const SESSION_KEY = 'synoria_companion_chat';

    public function show(Request $request, CulinaryCompanion $companion): View
    {
        $restaurant = null;
        if ($request->filled('restaurant')) {
            $term = $request->string('restaurant')->toString();
            $restaurant = Restaurant::query()
                ->when(
                    ctype_digit($term),
                    fn ($q) => $q->whereKey((int) $term),
                    fn ($q) => $q->where('slug', $term)
                )
                ->first();
        }

        $context = $companion->buildContext($request->user(), $restaurant);
        $history = $this->history($request);

        return view('companion.show', [
            'history' => $history,
            'context' => $context,
            'restaurant' => $restaurant,
            'suggestions' => [
                'J’ai 5000 FCFA',
                'Que me recommandes-tu ?',
                'Combien de temps d’attente ?',
            ],
        ]);
    }

    public function message(Request $request, CulinaryCompanion $companion): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:1000'],
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
        ]);

        $restaurant = null;
        if (! empty($validated['restaurant_id'])) {
            $restaurant = Restaurant::query()->find($validated['restaurant_id']);
        }

        $history = $this->history($request);
        $result = $companion->reply(
            $validated['message'],
            $request->user(),
            $restaurant,
            $history
        );

        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];
        $history = array_slice($history, -20);
        $request->session()->put(self::SESSION_KEY, $history);

        return response()->json([
            'reply' => $result['reply'],
            'suggestions' => $result['suggestions'],
            'mode' => $result['mode'],
            'history' => $history,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    private function history(Request $request): array
    {
        $history = $request->session()->get(self::SESSION_KEY, []);

        return is_array($history) ? array_values($history) : [];
    }
}
