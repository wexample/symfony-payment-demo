<?php

namespace Wexample\SymfonyPaymentDemo\Controller\Pages;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyPaymentDemo\Class\DemoPaymentProvider;
use Wexample\SymfonyPaymentDemo\Payable\DemoItemResolver;
use Wexample\SymfonyPaymentDemo\Traits\SymfonyPaymentDemoBundleClassTrait;

/**
 * Something bought, through symfony-payment-ds's pages, from a catalogue of
 * the demo's own; then, on the page of a provider pretended, paid, declined,
 * given up or left undecided — what a real provider's page would let the
 * payer do, and its webhook tell the application.
 */
#[Route(path: '/payment-demo/', name: 'payment_demo_')]
final class PaymentDemoController extends AbstractPagesController
{
    use SymfonyPaymentDemoBundleClassTrait;

    final public const string ROUTE_INDEX = VariableHelper::INDEX;

    final public const string ROUTE_CHECKOUT = 'checkout';

    final public const string ROUTE_OUTCOME = 'outcome';

    final public const string CSRF_TOKEN_ID = 'payment-demo-checkout';

    final public const array OUTCOMES = ['pay', 'decline', 'cancel', 'later'];

    #[Route(path: '', name: self::ROUTE_INDEX)]
    public function index(DemoItemResolver $items): Response
    {
        return $this->renderPage(self::ROUTE_INDEX, [
            'items' => $items->all(),
        ]);
    }

    #[Route(path: 'checkout/{reference}', name: self::ROUTE_CHECKOUT, methods: [Request::METHOD_GET])]
    public function checkout(
        string $reference,
        DemoPaymentProvider $provider,
    ): Response {
        $payment = $provider->fetch($reference);

        return $this->renderPage(self::ROUTE_CHECKOUT, [
            'reference' => $reference,
            'payment' => $payment,
            'outcomes' => self::OUTCOMES,
            'csrf_token_id' => self::CSRF_TOKEN_ID,
        ]);
    }

    #[Route(path: 'checkout/{reference}/{outcome}', name: self::ROUTE_OUTCOME, methods: [Request::METHOD_POST])]
    public function outcome(
        string $reference,
        string $outcome,
        Request $request,
        DemoPaymentProvider $provider,
        PaymentService $payments,
        PaymentRepository $repository,
    ): RedirectResponse {
        if (! in_array($outcome, self::OUTCOMES, true)
            || ! $this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->request->getString('_token'))) {
            throw $this->createNotFoundException();
        }

        match ($outcome) {
            'pay' => $payments->handleNotification($provider->succeed($reference)),
            'decline' => $payments->handleNotification($provider->fail($reference)),
            'cancel' => ($payment = $repository->findOneByProviderReference($reference)) ? $payments->cancel($payment) : null,
            // Back to the shop with nothing decided, as a payer leaving the
            // provider's page: the return page waits for the outcome.
            'later' => null,
        };

        return $this->redirect($provider->getReturnUrl($reference) ?? $this->generateUrl('payments_index'));
    }
}
