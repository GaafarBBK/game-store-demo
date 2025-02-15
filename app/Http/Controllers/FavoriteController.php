<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Game;


class FavoriteController extends Controller
{
    public function toggleFavorite($gameId)
    {
        $user = auth()->user();
        $game = Game::findOrFail($gameId);

        $existingFavorite = Favorite::where('user_id', $user->id)
            ->where('favorable_id', $game->id)
            ->where('favorable_type', Game::class)
            ->first();

        if ($existingFavorite) {
            $existingFavorite->delete();
            return response()->json(['message' => 'Game removed from favorites'], 200);
        }

        Favorite::create([
            'user_id' => $user->id,
            'favorable_id' => $game->id,
            'favorable_type' => Game::class,
        ]);

        return response()->json(['message' => 'Game added to favorites'], 200);
    }

    public function index()
    {
        $user = auth()->user();
        $favorites = Favorite::where('user_id', $user->id)
            ->where('favorable_type', Game::class)
            ->with('favorable') 
            ->paginate(10);

        return response()->json($favorites);
    }
}

