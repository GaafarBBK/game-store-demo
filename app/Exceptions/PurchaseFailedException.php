<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class PurchaseFailedException extends Exception
{
    public const INSUFFICIENT_FUNDS = 'insufficient_funds';
    public const INVALID_PAYMENT_METHOD = 'invalid_payment_method';
    public const SERVICE_UNAVAILABLE = 'service_unavailable';
    public const ALREADY_PURCHASED = 'already_purchased';
    public const PLATFORM_UNAVAILABLE = 'platform_unavailable';
    public const INVALID_CODE = 'invalid_code';
    public const ALREADY_REDEEMED = 'already_redeemed';
    

    private static array $errorMessages = [
        self::INSUFFICIENT_FUNDS => 'Insufficient funds in the account',
        self::INVALID_PAYMENT_METHOD => 'Payment method is invalid or expired',
        self::SERVICE_UNAVAILABLE => 'Payment service is currently unavailable',
        self::ALREADY_PURCHASED => 'Game has already been purchased for this platform',
        self::PLATFORM_UNAVAILABLE => 'Game is not available on the selected platform',
        self::INVALID_CODE => 'Invalid redeem code',
        self::ALREADY_REDEEMED => 'Code has already been redeemed',
    ];

    private array $errors;
    private string $errorType;

    public function __construct(
        string $errorType,
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null,
        array $errors = []
    ) {
        $this->errorType = $errorType;
        $this->errors = $errors;

        $message = $message ?: (self::$errorMessages[$errorType] ?? 'Purchase failed');
        
        parent::__construct($message, $code, $previous);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error_type' => $this->getErrorType(),
            'errors' => $this->getErrors(),
        ], $this->getCode());
    }
} 