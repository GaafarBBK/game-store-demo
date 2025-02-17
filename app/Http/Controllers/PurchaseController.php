<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PurchaseService;
use App\Models\Purchase;
use App\Models\Game;
use App\Models\Platform;
use App\Exceptions\PurchaseFailedException;


class PurchaseController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:games,id',
            'platform_id' => 'required|exists:platforms,id',
        ]);

        try {
            $game = Game::findOrFail($request->game_id);
            $platform = Platform::findOrFail($request->platform_id);

            if (!$game->platforms()->where('platform_id', $platform->id)->exists()) {
                throw new PurchaseFailedException(
                    errorType: PurchaseFailedException::PLATFORM_UNAVAILABLE,
                    errors: [
                        'game_id' => $game->name,
                        'platform_id' => $platform->name,
                        'available_platforms' => $game->platforms->pluck('name')->toArray()
                    ]
                );
            }

            if (Purchase::where('user_id', auth()->id())
                ->where('game_id', $game->id)
                ->where('platform_id', $platform->id)
                ->exists()
            ) {
                throw new PurchaseFailedException(
                    errorType: PurchaseFailedException::ALREADY_PURCHASED
                );
            }
            
            $purchase = Purchase::create([
                'game_id' => $game->id,
                'user_id' => auth()->id(),
                'platform_id' => $platform->id,
                'amount' => $game->price,
            ]);

            return response()->json([
                'message' => 'Purchase successful',
                'redeem_code' => $this->purchaseService->generateRedeemCode($purchase)],
                 200);

        } catch (PurchaseFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], $e->getCode());
        }

        // The following commented code is for the purchase service, but since there is no real API, I can't test it so I commented it out.
        // But check it out for future reference, It's a dynamic purchase service using a service layer with Dependency Injection, 
        // and a custom exception I created for the purchase service to handle many types of errors.
        
        /*
        try {
            $game = Game::find($request->game_id);
            $platform = Platform::find($request->platform_id);
            
            $this->purchaseService->purchaseGame($game, $platform);
            $redeemCode = $this->purchaseService->generateRedeemCode($purchase);
            
            return response()->json(['message' => 'Purchase successful', 'redeem_code' => $redeemCode]);

        } catch (PurchaseFailedException $e) {
            $message = $e->getMessage();     
            $code = $e->getCode();          
            $type = $e->getErrorType();     
            $errors = $e->getErrors();      
             
            return response()->json([
                'message' => $message,
                'errors' => $errors
            ], $code);
        }
        */
    }

    public function redeemCode(Request $request)
    {
        $request->validate([
            'redeem_code' => 'required|max:255',
        ]);

        try {
            $purchase = $this->purchaseService->redeemCode($request->redeem_code);
            return response()->json([
                'message' => 'Game redeemed successfully!',
                'purchase' => $purchase,
            ]);
        } catch (PurchaseFailedException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode());
        }
        
    }

    public function index(Request $request)
    {
        $query = Purchase::query()
            ->with([
                'game:id,name,price',
                'platform:id,name',
                'user:id,name'  
            ]);
        
        if ($request->user()->role === 'basic_user') {
            $query->where('user_id', auth()->id());
        }

        if ($request->user()->role === 'manager') {
            $query->whereIn('game_id', auth()->user()->games()->pluck('id'));
        }

        $purchases = $query->latest()->paginate(10);
        
        return response()->json($purchases);
    }
}
