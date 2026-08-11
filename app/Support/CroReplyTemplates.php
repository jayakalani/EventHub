<?php

namespace App\Support;

class CroReplyTemplates
{
    /**
     * @return list<array{key: string, label: string, body: string}>
     */
    public static function forInquiries(): array
    {
        return [
            [
                'key' => 'ack',
                'label' => 'Acknowledgement',
                'body' => "Thank you for contacting EventHub support. We've received your inquiry and are looking into it. We'll get back to you shortly with an update.",
            ],
            [
                'key' => 'event_info',
                'label' => 'Event details',
                'body' => "Thanks for reaching out. The event details (date, time, and venue) are shown on the event page and on your ticket. If anything has changed, you'll also receive a notification in your EventHub account.",
            ],
            [
                'key' => 'ticket_access',
                'label' => 'Ticket / QR help',
                'body' => "You can view and download your ticket from My Bookings in your EventHub account. Please make sure you're signed in with the same email used for the purchase. If the ticket still doesn't appear, reply here and we'll help.",
            ],
            [
                'key' => 'resolved',
                'label' => 'Issue resolved',
                'body' => "We've resolved your inquiry. If you still need help, reply to this message and we'll reopen the case.",
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, body: string}>
     */
    public static function forComplaints(): array
    {
        return [
            [
                'key' => 'ack',
                'label' => 'Acknowledgement',
                'body' => "Thank you for sharing your complaint. We're reviewing the details carefully and will follow up with you as soon as we have an update.",
            ],
            [
                'key' => 'payment',
                'label' => 'Payment review',
                'body' => "We're reviewing the payment details related to your complaint. Please allow us a little time to verify the transaction. We'll update you with the outcome shortly.",
            ],
            [
                'key' => 'refund_guidance',
                'label' => 'Refund guidance',
                'body' => "If your booking is eligible for a refund, you can submit a refund request from My Bookings. We'll also check eligibility on our side and update you if any action is needed from us.",
            ],
            [
                'key' => 'resolved',
                'label' => 'Complaint resolved',
                'body' => "We've completed our review and addressed your complaint. If anything is still unresolved, reply here and we'll continue assisting you.",
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, body: string}>
     */
    public static function forRefundDeclines(): array
    {
        return [
            [
                'key' => 'outside_window',
                'label' => 'Outside cancellation window',
                'body' => 'This refund request is outside the allowed cancellation window for the event, so we are unable to approve it under the current refund policy.',
            ],
            [
                'key' => 'policy',
                'label' => 'Not eligible per policy',
                'body' => 'Based on the event refund policy and the ticket status, this request does not qualify for a refund. If you believe this was assessed incorrectly, please contact support with additional details.',
            ],
            [
                'key' => 'event_completed',
                'label' => 'Event already completed',
                'body' => 'The event has already taken place, so this booking is no longer eligible for a cancellation refund.',
            ],
            [
                'key' => 'checked_in',
                'label' => 'Ticket already used',
                'body' => 'This ticket has already been checked in / used for entry, so a refund cannot be approved.',
            ],
        ];
    }
}
