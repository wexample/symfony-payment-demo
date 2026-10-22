<?php

namespace Wexample\SymfonyPaymentDemo\Payable;

use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Interface\PayableResolverInterface;

/**
 * The demo's catalogue, and the payable a payment of it refers to.
 */
class DemoItemResolver implements PayableResolverInterface
{
    /**
     * @return list<DemoItem>
     */
    public function all(): array
    {
        return [
            new DemoItem('poster', 'Poster of the design system', 1500, 'ph:bold/image'),
            new DemoItem('mug', 'Mug of the design system', 1290, 'ph:bold/coffee'),
            new DemoItem('membership', 'A year of membership', 12000, 'ph:bold/star'),
        ];
    }

    public function supports(string $payableType): bool
    {
        return DemoItem::getPayableType() === $payableType;
    }

    public function resolve(
        string $payableType,
        string $payableId
    ): ?PayableInterface {
        foreach ($this->all() as $item) {
            if ($item->id === $payableId) {
                return $item;
            }
        }

        return null;
    }
}
