<?php

namespace App\Http\Controllers;

use App\Services\StripeCheckoutService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected StripeCheckoutService $stripeCheckoutService,
    ) {}

    public function index(): View
    {
        $wallet = $this->walletService->getOrCreateWallet(Auth::user());
        $wallet->load(['transactions' => fn ($query) => $query->latest()->limit(20)]);

        return view('attendee.wallet.index', compact('wallet'));
    }

    public function topup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ]);

        if (! config('services.stripe.secret')) {
            return back()->withErrors(['amount' => 'Payment is not configured. Please contact support.']);
        }

        try {
            $session = $this->stripeCheckoutService->createWalletTopupSession(
                Auth::user(),
                (float) $validated['amount']
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['amount' => 'Unable to start top-up. Please try again.']);
        }

        return redirect()->away($session->url);
    }

    public function topupSuccess(Request $request): RedirectResponse
    {
        $sessionId = $request->query('session_id');

        if (! $sessionId) {
            return redirect()
                ->route('attendee.wallet.index')
                ->withErrors(['topup' => 'Invalid checkout session.']);
        }

        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Throwable $e) {
            return redirect()
                ->route('attendee.wallet.index')
                ->withErrors(['topup' => 'Unable to verify payment session.']);
        }

        $payment = \App\Models\Payment::query()
            ->where('stripe_session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->where('purpose', 'wallet_topup')
            ->firstOrFail();

        if ($session->payment_status === 'paid') {
            $this->stripeCheckoutService->fulfillWalletTopup(
                $payment,
                is_string($session->payment_intent) ? $session->payment_intent : null
            );
        }

        return redirect()
            ->route('attendee.wallet.index')
            ->with('success', 'Wallet topped up successfully!');
    }
}
