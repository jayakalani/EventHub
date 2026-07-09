<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation Expiring Soon</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px;">
    <h1 style="color: #4f46e5; margin-bottom: 8px;">Complete your payment</h1>
    <p>Hi {{ $user->name }},</p>
    <p>
        Your reserved tickets for <strong>{{ $cartItem->event->name }}</strong>
        will expire in <strong>{{ $minutesRemaining }} minute(s)</strong>.
    </p>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 20px 0;">
        <p style="margin: 0;"><strong>Event:</strong> {{ $cartItem->event->name }}</p>
        <p style="margin: 8px 0 0;"><strong>Category:</strong> {{ $cartItem->ticketCategory?->name }}</p>
        <p style="margin: 8px 0 0;"><strong>Quantity:</strong> {{ $cartItem->quantity }}</p>
        <p style="margin: 8px 0 0;"><strong>Expires at:</strong> {{ $cartItem->expiresAt()->format('M j, Y g:i A') }}</p>
    </div>

    <p>
        <a href="{{ route('attendee.cart.index') }}"
            style="display: inline-block; background: #4f46e5; color: #fff; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: bold;">
            Go to cart & pay now
        </a>
    </p>

    <p style="color: #64748b; font-size: 14px; margin-top: 24px;">
        If you do not complete payment before the reservation expires, your tickets will be released for others to book.
    </p>
</body>
</html>
