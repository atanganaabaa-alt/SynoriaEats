<?php

namespace App\Services\Payments;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $reference = null,
        public ?string $message = null,
        public bool $pending = false,
        public ?string $redirectUrl = null,
    ) {}

    public static function paid(string $reference): self
    {
        return new self(success: true, reference: $reference);
    }

    public static function failed(string $message): self
    {
        return new self(success: false, message: $message);
    }

    public static function pending(string $reference, string $redirectUrl): self
    {
        return new self(success: false, reference: $reference, pending: true, redirectUrl: $redirectUrl);
    }
}
