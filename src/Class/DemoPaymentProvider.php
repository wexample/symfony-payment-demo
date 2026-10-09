<?php

namespace Wexample\SymfonyPaymentDemo\Class;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyRemotePayment\Class\FakePaymentProvider;
use Wexample\SymfonyRemotePayment\Class\PaymentInitiation;
use Wexample\SymfonyRemotePayment\Class\PaymentNotification;
use Wexample\SymfonyRemotePayment\Class\PaymentRequest;
use Wexample\SymfonyRemotePayment\Class\ProviderPayment;
use Wexample\SymfonyRemotePayment\Class\ProviderRefund;
use Wexample\SymfonyRemotePayment\Interface\PaymentGatewayInterface;

/**
 * A provider pretended, for the demo: symfony-remote-payment's fake one, kept
 * from one request to the next in the cache — the fake holds its payments in
 * memory —, sending the payer to a page of its own where they pay, decline
 * or give up, as on a provider's page.
 */
class DemoPaymentProvider implements PaymentGatewayInterface
{
    final public const string NAME = 'demo';

    private const string CACHE_KEY = 'payment_demo.provider';

    private const string CACHE_KEY_RETURN = 'payment_demo.return.';

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function supports(string $method): bool
    {
        return 'card' === $method;
    }

    public function initiate(PaymentRequest $request): PaymentInitiation
    {
        $initiation = $this->call(static fn (FakePaymentProvider $fake) => $fake->initiate($request));
        $this->save(self::CACHE_KEY_RETURN.$initiation->providerReference, $request->returnUrl);

        return new PaymentInitiation(
            $initiation->providerReference,
            $initiation->status,
            redirectUrl: $this->urlGenerator->generate(
                'payment_demo_checkout',
                ['reference' => $initiation->providerReference],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        );
    }

    public function fetch(string $providerReference): ProviderPayment
    {
        return $this->call(static fn (FakePaymentProvider $fake) => $fake->fetch($providerReference));
    }

    public function cancel(string $providerReference): ProviderPayment
    {
        return $this->call(static fn (FakePaymentProvider $fake) => $fake->cancel($providerReference));
    }

    public function refund(
        string $providerReference,
        ?int $amount = null,
        ?string $reason = null
    ): ProviderRefund {
        return $this->call(static fn (FakePaymentProvider $fake) => $fake->refund($providerReference, $amount, $reason));
    }

    /**
     * The payer paid, on the demo's page: what the provider's webhook would say.
     */
    public function succeed(string $providerReference): PaymentNotification
    {
        return $this->call(static fn (FakePaymentProvider $fake) => $fake->succeed($providerReference));
    }

    public function fail(string $providerReference): PaymentNotification
    {
        return $this->call(static fn (FakePaymentProvider $fake) => $fake->fail($providerReference));
    }

    /**
     * Where the payer goes back to once done on the demo's page.
     */
    public function getReturnUrl(string $providerReference): ?string
    {
        $item = $this->cache->getItem(self::CACHE_KEY_RETURN.$providerReference);

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * The fake, read from the cache, asked, and put back with what it now holds.
     *
     * @template T
     * @param callable(FakePaymentProvider): T $call
     * @return T
     */
    private function call(callable $call): mixed
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        $fake = $item->isHit() ? $item->get() : new FakePaymentProvider(self::NAME, ['card']);
        $result = $call($fake);
        $this->cache->save($item->set($fake));

        return $result;
    }

    private function save(string $key, mixed $value): void
    {
        $this->cache->save($this->cache->getItem($key)->set($value));
    }
}
