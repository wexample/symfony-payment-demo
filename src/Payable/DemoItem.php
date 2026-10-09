<?php

namespace Wexample\SymfonyPaymentDemo\Payable;

use Wexample\SymfonyPayment\Interface\PayableInterface;

/**
 * Something the demo sells: a name, a price in euro cents. No table behind
 * it — the catalogue is the demo's (DemoItemResolver).
 */
final readonly class DemoItem implements PayableInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public int $amount,
        public string $icon,
    ) {
    }

    public static function getPayableType(): string
    {
        return 'demo_item';
    }

    public function getPayableId(): string
    {
        return $this->id;
    }

    public function getPayableAmount(): int
    {
        return $this->amount;
    }

    public function getPayableCurrencyCode(): string
    {
        return 'EUR';
    }

    public function getPayableDescription(): ?string
    {
        return $this->name;
    }
}
