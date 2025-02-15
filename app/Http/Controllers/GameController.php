<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game;

class GameController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'manager' => 'exists:users,id',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'youtube_url' => 'string',
            'genres.*' => 'required|string',
            'platforms.*' => 'required|string',
            'cryptocurrencies.*' => 'required|string',
        ]);

        $game = Game::create([
            'name' => $request->name,
            'manager' => $request->manager ?? $request->user()->id,
            'price' => $request->price,
            'description' => $request->description,
            'image' => $request->image ?? null,
            'youtube_url' => $request->youtube_url ?? null,
        ]);

        $game->genres()->attach($request->genres);
        $game->platforms()->attach($request->platforms);
        $game->cryptocurrencies()->attach($request->cryptocurrencies);

        return response()->json($game, 201);
    }

    public function search(Request $request)
    {
        $query = Game::query();

        if ($request->has('query')) {
            $searchTerm = $request->input('query');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($request->has('platform')) {
            $query->whereHas('platforms', function ($q) use ($request) {
                $q->where('id', $request->platform);
            });
        }

        if ($request->has('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('id', $request->genre);
            });
        }

        if ($request->has('cryptocurrency')) {
            $query->whereHas('cryptocurrencies', function ($q) use ($request) {
                $q->where('id', $request->cryptocurrency);
            });
        }

        if ($request->has('sort')) {
            $direction = $request->input('direction', 'asc'); 
            $direction = in_array($direction, ['asc', 'desc']) ? $direction : 'asc'; 

            switch ($request->sort) {
                case 'rating':
                    $query->withAvg('reviews', 'rating')
                          ->orderBy('reviews_avg_rating', $direction);
                    break;
                case 'price':
                    $query->orderBy('price', $direction);
                    break;
                default:
                    $query->latest();
                    break;
            }
        }

        $perPage = $request->input('per_page', 10);
        $games = $query->with(['platforms', 'genres', 'cryptocurrencies'])->paginate($perPage);

        return response()->json($games);
    }

    public function show($id)
    {
        $game = Game::with([
            'platforms',
            'genres',
            'cryptocurrencies',
            'reviews' => function($query) {
                $query->latest()->limit(5); 
            },
            'reviews.user:id,name'
        ])->findOrFail($id);

        return response()->json($game);
    }

    public function update(Request $request, $id)
    {
        $game = Game::find($id);

        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }

        if ($request->user()->id !== $game->manager) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'youtube_url' => 'string',
            'genres.*' => 'string',
            'platforms.*' => 'string',
            'cryptocurrencies.*' => 'string',
        ]);
        
        $game->update($request->only(['name', 'description', 'image', 'youtube_url']));

        if ($request->has('platforms')) {
            $game->platforms()->sync($request->platforms);
        }
        if ($request->has('genres')) {
            $game->genres()->sync($request->genres);
        }
        if ($request->has('cryptocurrencies')) {
            $game->cryptocurrencies()->sync($request->cryptocurrencies);
        }

        return response()->json([
            'message' => 'Game updated successfully',
            'game' => $game
        ]);
    }

    public function destroy($id)
    {
        $game = Game::find($id);

        if (!$game) {
            return response()->json(['message' => 'Game not found'], 404);
        }

        if ($game->manager !== auth()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $game->delete();
    
        return response()->json(['message' => 'Game deleted successfully']);
    }
}
