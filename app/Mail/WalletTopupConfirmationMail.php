<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletTopupConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Wallet top-up confirmed — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        $this->payment->load('user.wallet');

        return new Content(
            view: 'mail.wallet-topup-confirmation',
            with: ['payment' => $this->payment],
        );
    }
}
