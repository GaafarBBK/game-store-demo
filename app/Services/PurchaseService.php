<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\User;
use App\Models\Game;
use App\Models\Platform;
use App\Exceptions\PurchaseFailedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->baseUrl = config('services.bank_api.base_url');
        $this->apiKey = config('services.bank_api.api_key');
    }

    /**
     * @throws PurchaseFailedException
     */
    
    public function purchaseGame(Game $game, Platform $platform): Purchase
    {

        
        if (!$game->platforms()->where('platform_id', $platform->id)->exists()) {
            throw new PurchaseFailedException(
                errorType: PurchaseFailedException::PLATFORM_UNAVAILABLE,
                errors: [
                    'game_id' => $game->id,
                    'platform_id' => $platform->id,
                    'available_platforms' => $game->platforms->pluck('name')->toArray()
                ]
            );
        }

        if ($this->user->purchases()
            ->where('game_id', $game->id)
            ->where('platform_id', $platform->id)
            ->exists()
        ) {
            throw new PurchaseFailedException(
                errorType: PurchaseFailedException::ALREADY_PURCHASED,
                errors: [
                    'user_id' => $this->user->id,
                    'game_id' => $game->id,
                    'platform_id' => $platform->id
                ]
            );
        }

        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/purchase', [
                'user_id' => $this->user->id,
                'game_id' => $game->id,
                'platform_id' => $platform->id,
                'amount' => $game->price,
            ]);

            if (!$response->successful()) {
                // To handle specific payment API error responses, hypothetical but for real scenario (like libyan bank services) I would check documentation for error codes.  
                $errorType = match ($response->json('error_code')) {
                    'insufficient_balance' => PurchaseFailedException::INSUFFICIENT_FUNDS,
                    'invalid_payment_method' => PurchaseFailedException::INVALID_PAYMENT_METHOD,
                    'service_error' => PurchaseFailedException::SERVICE_UNAVAILABLE,
                    default => PurchaseFailedException::SERVICE_UNAVAILABLE,
                };

                throw new PurchaseFailedException(
                    errorType: $errorType,
                    errors: [
                        'api_response' => $response->json(),
                        'status_code' => $response->status()
                    ]
                );
            }

            return Purchase::create([
                'user_id' => $this->user->id,
                'game_id' => $game->id,
                'platform_id' => $platform->id,
                'amount' => $game->price,
                'transaction_id' => $response->json('transaction_id')
            ]);

        } catch (\Exception $e) {
            if ($e instanceof PurchaseFailedException) {
                throw $e;
            }

            throw new PurchaseFailedException(
                errorType: PurchaseFailedException::SERVICE_UNAVAILABLE,
                message: 'Failed to process purchase: ' . $e->getMessage()
            );
        }
    }

    public function generateRedeemCode(Purchase $purchase): string {
        return base62_encode($purchase->id); 
    }

    public function redeemCode(string $redeemCode): Purchase {
        return DB::transaction(function () use ($redeemCode) {
            $purchaseId = base62_decode($redeemCode);

            $purchase = Purchase::lockForUpdate()->find($purchaseId);

            if (!$purchase) {
                throw new PurchaseFailedException(
                    errorType: PurchaseFailedException::INVALID_CODE,
                    message: 'Invalid redeem code.',
                    code: 400
                );
            }

            if ($purchase->redeemed_at !== null) {
                throw new PurchaseFailedException(
                    errorType: PurchaseFailedException::ALREADY_REDEEMED,
                    message: 'Code already redeemed.',
                    code: 409
                );
            }

            $purchase->redeemed_at = now();
            $purchase->save();

            return $purchase;
        });
    }
}