<?php

namespace App\Http\Controllers\Api;

use App\Enums\MenuCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Restaurant;
use App\Services\CloudinaryUploader;
use App\Services\RestaurantMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function __construct(
        private CloudinaryUploader $media,
        private RestaurantMatcher $matcher,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString() ?: ($request->filled(['lat', 'lng']) ? 'relevant' : 'rating');

        $query = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('min_rating'), fn ($q) => $q->where('rating', '>=', (float) $request->input('min_rating')))
            ->when($request->filled('max_fee'), fn ($q) => $q->where('delivery_fee', '<=', (int) $request->input('max_fee')));

        if ($sort === 'relevant' && $request->filled(['lat', 'lng'])) {
            $ranked = $this->matcher->rank(
                $query->get(),
                (float) $request->input('lat'),
                (float) $request->input('lng')
            );

            return response()->json([
                'data' => $ranked->values(),
            ]);
        }

        $restaurants = $query
            ->when($sort === 'fee', fn ($q) => $q->orderBy('delivery_fee')->orderByDesc('rating'))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when(! in_array($sort, ['fee', 'name'], true), fn ($q) => $q->orderByDesc('rating'))
            ->paginate(20);

        return response()->json($restaurants);
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        abort_unless($restaurant->is_validated, 404);

        $restaurant->load([
            'menuItems' => fn ($q) => $q
                ->where('is_available', true)
                ->where('category', '!=', MenuCategory::Accompagnements->value)
                ->orderBy('category')
                ->orderBy('name'),
        ]);

        return response()->json($restaurant);
    }

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $data = $request->validatedRestaurant();

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $this->media->upload($request->file('logo'), 'restaurants/logos');
        }

        if ($request->hasFile('cover')) {
            $data['cover_url'] = $this->media->upload($request->file('cover'), 'restaurants/covers');
        }

        $restaurant = Restaurant::query()->create($data);

        return response()->json($restaurant, 201);
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
    {
        $data = $request->safe()->except(['logo', 'cover']);
        $data['is_open'] = $request->boolean('is_open');

        if ($request->hasFile('logo')) {
            $this->media->deleteIfLocal($restaurant->logo_url);
            $data['logo_url'] = $this->media->upload($request->file('logo'), 'restaurants/logos');
        }

        if ($request->hasFile('cover')) {
            $this->media->deleteIfLocal($restaurant->cover_url);
            $data['cover_url'] = $this->media->upload($request->file('cover'), 'restaurants/covers');
        }

        $restaurant->update($data);

        return response()->json($restaurant->fresh());
    }
}
