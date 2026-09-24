<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\UserPreference;
use App\Services\ConversationalAiAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiConversationController extends Controller
{
    public function show(Request $request, ConversationalAiAgent $agent): View
    {
        $restaurant = $this->resolveRestaurant($request);
        $sessionKey = $this->sessionKey($request);
        $history = $agent->loadHistory($request->user(), $sessionKey);
        $context = $agent->buildContext(
            $request->user(),
            $restaurant,
            $this->matchPreferences($request),
            $this->clientLocation($request),
        );

        return view('companion.show', [
            'history' => $history,
            'context' => $context,
            'restaurant' => $restaurant,
            'agentName' => $agent->agentName(),
            'configured' => $agent->isConfigured(),
            'suggestions' => $agent->quickSuggestions($context),
        ]);
    }

    /** Historique JSON (alias API Sara + /companion/history). */
    public function index(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        return $this->history($request, $agent);
    }

    public function history(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $history = $agent->loadHistory($request->user(), $this->sessionKey($request));

        return response()->json([
            'history' => $history,
            'agent' => $agent->agentName(),
            'configured' => $agent->isConfigured(),
            'preferences' => $this->userTastes($request),
            'suggestions' => $agent->quickSuggestions(
                $agent->buildContext(
                    $request->user(),
                    $this->resolveRestaurant($request),
                    $this->matchPreferences($request),
                    $this->clientLocation($request),
                )
            ),
        ]);
    }

    /** Envoi message (alias API Sara + /companion/message). */
    public function sendMessage(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        return $this->message($request, $agent);
    }

    public function message(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1500'],
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
        ]);

        $restaurant = null;
        if (! empty($validated['restaurant_id'])) {
            $restaurant = Restaurant::query()->find($validated['restaurant_id']);
        }

        $sessionKey = $this->sessionKey($request);
        $user = $request->user();

        // Mémoire courte pour le LLM (10 derniers tours)
        $history = $agent->loadHistory($user, $sessionKey, 10);

        // Préférences apprises (création lazy)
        if ($user) {
            UserPreference::query()->firstOrCreate(
                ['user_id' => $user->id],
                ['tastes' => []]
            );
        }

        $result = $agent->reply(
            $validated['message'],
            $user,
            $restaurant,
            $history,
            $this->matchPreferences($request),
            $this->clientLocation($request),
        );

        $agent->persistTurn(
            $user,
            $sessionKey,
            $validated['message'],
            $result['reply'],
            $restaurant?->id,
        );

        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];

        return response()->json([
            'reply' => $result['reply'],
            'suggestions' => $result['suggestions'],
            'mode' => $result['mode'],
            'agent' => $result['agent'],
            'preferences' => $result['preferences'] ?? $this->userTastes($request),
            'history' => array_slice($history, -40),
        ]);
    }

    public function reset(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $agent->clearHistory($request->user(), $this->sessionKey($request));

        return response()->json([
            'ok' => true,
            'agent' => $agent->agentName(),
        ]);
    }

    private function sessionKey(Request $request): string
    {
        return substr(hash('sha256', $request->session()->getId()), 0, 64);
    }

    private function resolveRestaurant(Request $request): ?Restaurant
    {
        if ($request->filled('restaurant_id')) {
            return Restaurant::query()->find((int) $request->input('restaurant_id'));
        }

        if (! $request->filled('restaurant')) {
            return null;
        }

        $term = $request->string('restaurant')->toString();

        return Restaurant::query()
            ->when(
                ctype_digit($term),
                fn ($q) => $q->whereKey((int) $term),
                fn ($q) => $q->where('slug', $term)
            )
            ->first();
    }

    /**
     * @return array{lat: ?float, lng: ?float}
     */
    private function clientLocation(Request $request): array
    {
        $lat = $request->filled('lat')
            ? (float) $request->input('lat')
            : ($request->session()->get('client_lat') ? (float) $request->session()->get('client_lat') : null);
        $lng = $request->filled('lng')
            ? (float) $request->input('lng')
            : ($request->session()->get('client_lng') ? (float) $request->session()->get('client_lng') : null);

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * @return array<string, mixed>
     */
    private function matchPreferences(Request $request): array
    {
        $weights = $request->session()->get('match_weights');
        $preset = $request->session()->get('match_preset');

        if (! $weights && ! $preset) {
            return [];
        }

        return array_filter([
            'preset' => $preset,
            'weights' => is_array($weights) ? $weights : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userTastes(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        $pref = UserPreference::query()->where('user_id', $user->id)->first();

        return is_array($pref?->tastes) ? $pref->tastes : [];
    }
}
