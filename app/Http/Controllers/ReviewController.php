<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Game;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, $gameId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        

        if (!$user->purchases()->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'You must purchase the game before reviewing it'], 403);
        }

        if ($user->reviews()->where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'You already reviewed this game'], 409);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'game_id' => $gameId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review, 201);
    }

    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rating' => 'integer|min:1|max:5',
            'comment' => 'string|max:500',
        ]);

        $review->update($request->only(['rating', 'comment']));

        return response()->json($review);
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
    }

    public function index($gameId)
    {
        $reviews = Review::where('game_id', $gameId)
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($reviews);
    }


}
