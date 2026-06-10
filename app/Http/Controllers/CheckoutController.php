<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class CheckoutController extends Controller
{
    public function __construct(
        protected StripeCheckoutService $stripeCheckoutService
    ) {}

    public function success(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()
                ->route('attendee.cart.index')
                ->withErrors(['checkout' => 'Invalid checkout session.']);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            return redirect()
                ->route('attendee.cart.index')
                ->withErrors(['checkout' => 'Unable to verify payment session.']);
        }

        $payment = Payment::query()
            ->where('stripe_session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($session->payment_status === 'paid') {
            try {
                $this->stripeCheckoutService->fulfillPayment(
                    $payment,
                    is_string($session->payment_intent) ? $session->payment_intent : null
                );
            } catch (\RuntimeException $e) {
                return redirect()
                    ->route('attendee.cart.index')
                    ->withErrors(['checkout' => $e->getMessage()]);
            }
        }

        return redirect()
            ->route('attendee.bookings.index')
            ->with('success', 'Payment successful! Your tickets are ready.');
    }

    public function cancel(Request $request): RedirectResponse
    {
        if ($request->filled('payment_id')) {
            $payment = Payment::query()
                ->where('id', $request->query('payment_id'))
                ->where('user_id', Auth::id())
                ->first();

            if ($payment) {
                $this->stripeCheckoutService->markPaymentCancelled($payment);
            }
        }

        return redirect()
            ->route('attendee.cart.index')
            ->withErrors(['checkout' => 'Payment was cancelled. Your cart items are still reserved.']);
    }

    public function webhook(Request $request): Response
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;

            if ($session->payment_status !== 'paid') {
                return response('Ignored', 200);
            }

            $payment = Payment::query()
                ->where('stripe_session_id', $session->id)
                ->first();

            if ($payment) {
                try {
                    $this->stripeCheckoutService->fulfillPayment(
                        $payment,
                        is_string($session->payment_intent) ? $session->payment_intent : null
                    );
                } catch (\RuntimeException $e) {
                    Log::error('Stripe payment fulfillment failed.', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return response('OK', 200);
    }
}
