<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Services\StripeCheckoutService;
use Illuminate\Console\Command;
use Stripe\Stripe;
use Throwable;

class FulfillPaidStripePayments extends Command
{
    protected $signature = 'payments:fulfill-paid {payment? : Payment ID to fulfill}';

    protected $description = 'Fulfill Stripe payments that are already paid but still pending locally';

    public function handle(StripeCheckoutService $stripeCheckoutService): int
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            $this->error('Stripe is not configured.');

            return self::FAILURE;
        }

        Stripe::setApiKey($secret);

        $query = Payment::query()
            ->where('status', PaymentStatusEnum::Pending)
            ->whereNotNull('stripe_session_id')
            ->when(
                $this->argument('payment'),
                fn ($q) => $q->whereKey((int) $this->argument('payment')),
            );

        $payments = $query->get();

        if ($payments->isEmpty()) {
            $this->info('No pending Stripe payments to fulfill.');

            return self::SUCCESS;
        }

        $fulfilled = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            try {
                $session = \Stripe\Checkout\Session::retrieve($payment->stripe_session_id);
            } catch (Throwable $e) {
                $this->error("Payment #{$payment->id}: unable to load Stripe session ({$e->getMessage()})");
                $failed++;

                continue;
            }

            if ($session->payment_status !== 'paid') {
                $this->line("Payment #{$payment->id}: Stripe status is {$session->payment_status}, skipped.");
                $skipped++;

                continue;
            }

            try {
                $stripeCheckoutService->fulfillPayment(
                    $payment,
                    is_string($session->payment_intent) ? $session->payment_intent : null,
                );
            } catch (Throwable $e) {
                $this->error("Payment #{$payment->id}: fulfillment failed ({$e->getMessage()})");
                $failed++;

                continue;
            }

            $payment->refresh();

            if (! $payment->isCompleted()) {
                $this->error("Payment #{$payment->id}: still pending after fulfill.");
                $failed++;

                continue;
            }

            $this->info("Payment #{$payment->id} ({$payment->reference}) fulfilled.");
            $fulfilled++;
        }

        $this->info("Done. Fulfilled {$fulfilled}, skipped {$skipped}, failed {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
