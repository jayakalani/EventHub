<?php

namespace App\Services;

use App\Enums\CroNotificationCategory;
use App\Models\Event;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\CroActivityNotification;
use Illuminate\Support\Collection;

class CroNotificationService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function send(
        ?User $user,
        CroNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        if (! $user || ! $this->isCro($user)) {
            return;
        }

        $user->notify(new CroActivityNotification(
            $category,
            $type,
            $message,
            $url,
            $metadata,
        ));
    }

    /**
     * Notify the CRO assigned to an event (contact_person).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function notifyEventCro(
        Event $event,
        CroNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        $event->loadMissing('contactPerson.userRole');

        $this->send(
            $event->contactPerson,
            $category,
            $type,
            $message,
            $url,
            array_merge(['event_id' => $event->id], $metadata),
        );
    }

    /**
     * Notify every active CRO (e.g. platform-wide complaints).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function notifyAllCros(
        CroNotificationCategory $category,
        string $type,
        string $message,
        string $url,
        array $metadata = [],
    ): void {
        foreach ($this->allCros() as $cro) {
            $this->send($cro, $category, $type, $message, $url, $metadata);
        }
    }

    public function notifyEventAssigned(Event $event): void
    {
        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Assignment,
            'event_assigned',
            'You have been assigned as Customer Relations Officer for "'.$event->name.'".',
            route('cro.dashboard', ['event' => $event->id]),
        );
    }

    public function notifyInquirySubmitted(Event $event, int $inquiryId, string $subject): void
    {
        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Interaction,
            'inquiry_submitted',
            'New inquiry about "'.$event->name.'": "'.$subject.'".',
            route('cro.inquiries.show', $inquiryId),
            ['inquiry_id' => $inquiryId],
        );
    }

    public function notifyComplaintSubmitted(int $complaintId, string $subject): void
    {
        $this->notifyAllCros(
            CroNotificationCategory::Interaction,
            'complaint_submitted',
            'A new complaint was submitted: "'.$subject.'".',
            route('cro.complaints.show', $complaintId),
            ['complaint_id' => $complaintId],
        );
    }

    public function notifyRefundRequestSubmitted(Event $event, int $refundRequestId): void
    {
        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Refund,
            'refund_request_submitted',
            'A refund request was submitted for "'.$event->name.'" and needs review.',
            route('cro.refund-requests.show', $refundRequestId),
            ['refund_request_id' => $refundRequestId],
        );
    }

    public function notifyEventPostponed(Event $event, string $reason = ''): void
    {
        $message = 'Your assigned event "'.$event->name.'" was postponed.';
        if ($reason !== '') {
            $message .= ' Reason: '.$reason;
        }
        $message .= ' Open the handoff checklist to clear inquiries and refunds.';

        $handoff = app(CroHandoffService::class)->forEvent($event);

        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Event,
            'event_postponed',
            $message,
            route('cro.handoffs.show', $event),
            [
                'handoff' => [
                    'type' => 'postponed',
                    'open_inquiries' => $handoff['summary']['openInquiries'],
                    'pending_refunds' => $handoff['summary']['pendingRefunds'],
                ],
            ],
        );
    }

    public function notifyEventRescheduled(Event $event): void
    {
        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Event,
            'event_rescheduled',
            'Your assigned event "'.$event->name.'" was rescheduled.',
            route('cro.dashboard'),
        );
    }

    public function notifyEventCancelled(Event $event, string $reason = ''): void
    {
        $message = 'Your assigned event "'.$event->name.'" was cancelled.';
        if ($reason !== '') {
            $message .= ' Reason: '.$reason;
        }
        $message .= ' Open the handoff checklist to clear inquiries and refunds.';

        $handoff = app(CroHandoffService::class)->forEvent($event);

        $this->notifyEventCro(
            $event,
            CroNotificationCategory::Event,
            'event_cancelled',
            $message,
            route('cro.handoffs.show', $event),
            [
                'handoff' => [
                    'type' => 'cancelled',
                    'open_inquiries' => $handoff['summary']['openInquiries'],
                    'pending_refunds' => $handoff['summary']['pendingRefunds'],
                ],
            ],
        );
    }

    public function notifyEventStartsTomorrow(Event $event): void
    {
        $event->loadMissing('contactPerson.userRole');
        $cro = $event->contactPerson;

        if (! $cro || ! $this->isCro($cro)) {
            return;
        }

        $alreadySent = $cro->notifications()
            ->where('data->type', 'event_starts_tomorrow')
            ->where('data->event_id', $event->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadySent) {
            return;
        }

        $this->send(
            $cro,
            CroNotificationCategory::Reminder,
            'event_starts_tomorrow',
            'Reminder: your assigned event "'.$event->name.'" starts tomorrow.',
            route('cro.dashboard', ['event' => $event->id]),
            ['event_id' => $event->id],
        );
    }

    /**
     * @return Collection<int, User>
     */
    public function allCros(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('userRole', fn ($q) => $q->where('name_en', UserRole::CRO))
            ->get();
    }

    public function isCro(User $user): bool
    {
        $user->loadMissing('userRole');

        return $user->userRole?->name_en === UserRole::CRO;
    }
}
