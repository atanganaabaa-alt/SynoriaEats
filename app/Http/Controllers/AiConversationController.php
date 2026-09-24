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
        $conversations = $agent->listConversations($request->user(), $sessionKey);
        $activeId = (int) $request->input('c', $conversations[0]['id'] ?? 0) ?: null;
        $history = $agent->loadHistory($request->user(), $sessionKey, 80, $activeId);
        $context = $agent->buildContext(
            $request->user(),
            $restaurant,
            $this->matchPreferences($request),
            $this->clientLocation($request),
        );

        return view('companion.show', [
            'history' => $history,
            'conversations' => $conversations,
            'activeConversationId' => $activeId,
            'context' => $context,
            'restaurant' => $restaurant,
            'agentName' => $agent->agentName(),
            'engine' => $agent->engineLabel(),
            'configured' => $agent->isConfigured(),
            'suggestions' => $agent->quickSuggestions($context),
        ]);
    }

    /** Liste des fils + messages du fil actif. */
    public function index(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        return $this->history($request, $agent);
    }

    public function history(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $sessionKey = $this->sessionKey($request);
        $conversationId = $request->filled('conversation_id')
            ? (int) $request->input('conversation_id')
            : null;

        $conversations = $agent->listConversations($request->user(), $sessionKey);
        if (! $conversationId && $conversations !== []) {
            $conversationId = (int) $conversations[0]['id'];
        }

        $history = $agent->loadHistory($request->user(), $sessionKey, 80, $conversationId);

        return response()->json([
            'history' => $history,
            'conversations' => $conversations,
            'conversation_id' => $conversationId,
            'agent' => $agent->agentName(),
            'engine' => $agent->engineLabel(),
            'mode' => $agent->resolveProvider(),
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

    public function conversations(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        return response()->json([
            'conversations' => $agent->listConversations($request->user(), $this->sessionKey($request)),
            'engine' => $agent->engineLabel(),
        ]);
    }

    public function storeConversation(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $restaurantId = $request->filled('restaurant_id')
            ? (int) $request->input('restaurant_id')
            : null;

        $conversation = $agent->createConversation(
            $request->user(),
            $this->sessionKey($request),
            $restaurantId,
        );

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'preview' => null,
            ],
            'conversations' => $agent->listConversations($request->user(), $this->sessionKey($request)),
        ], 201);
    }

    public function destroyConversation(Request $request, ConversationalAiAgent $agent, int $conversation): JsonResponse
    {
        $ok = $agent->deleteConversation($request->user(), $this->sessionKey($request), $conversation);

        return response()->json([
            'ok' => $ok,
            'conversations' => $agent->listConversations($request->user(), $this->sessionKey($request)),
        ]);
    }

    public function sendMessage(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        return $this->message($request, $agent);
    }

    public function message(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:1500'],
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:companion_conversations,id'],
        ]);

        $restaurant = null;
        if (! empty($validated['restaurant_id'])) {
            $restaurant = Restaurant::query()->find($validated['restaurant_id']);
        }

        $sessionKey = $this->sessionKey($request);
        $user = $request->user();

        $conversation = $agent->resolveOrCreateConversation(
            $user,
            $sessionKey,
            isset($validated['conversation_id']) ? (int) $validated['conversation_id'] : null,
            $restaurant?->id,
        );

        $history = $agent->loadHistory($user, $sessionKey, 10, $conversation->id);

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

        $conversation = $agent->persistTurn(
            $user,
            $sessionKey,
            $validated['message'],
            $result['reply'],
            $restaurant?->id,
            $conversation,
        );

        $history[] = ['role' => 'user', 'content' => $validated['message']];
        $history[] = ['role' => 'assistant', 'content' => $result['reply']];

        return response()->json([
            'reply' => $result['reply'],
            'suggestions' => $result['suggestions'],
            'mode' => $result['mode'],
            'engine' => $agent->engineLabel(),
            'agent' => $result['agent'],
            'conversation_id' => $conversation->id,
            'conversation_title' => $conversation->title,
            'preferences' => $result['preferences'] ?? $this->userTastes($request),
            'history' => array_slice($history, -40),
            'conversations' => $agent->listConversations($user, $sessionKey),
        ]);
    }

    public function reset(Request $request, ConversationalAiAgent $agent): JsonResponse
    {
        $conversationId = $request->filled('conversation_id')
            ? (int) $request->input('conversation_id')
            : null;

        $agent->clearHistory($request->user(), $this->sessionKey($request), $conversationId);

        return response()->json([
            'ok' => true,
            'agent' => $agent->agentName(),
            'conversations' => $agent->listConversations($request->user(), $this->sessionKey($request)),
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
