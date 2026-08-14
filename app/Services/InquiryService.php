<?php

namespace App\Services;

use App\Enums\AttendeeNotificationCategory;
use App\Enums\SupportTicketStatusEnum;
use App\Mail\InquiryAnsweredMail;
use App\Mail\InquiryReceivedMail;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class InquiryService
{
    public function __construct(
        protected SupportAuditService $auditService,
    ) {}

    public function submit(User $user, Event $event, string $subject, string $message): Inquiry
    {
        return DB::transaction(function () use ($user, $event, $subject, $message) {
            $event->loadMissing('contactPerson.userRole');

            $inquiry = Inquiry::create([
                'user_id' => $user->id,
                'event_id' => $event->id,
                'subject' => $subject,
                'message' => $message,
                'status' => SupportTicketStatusEnum::Open,
                'assigned_to' => $event->contact_person,
            ]);

            $inquiry->load(['user', 'event.contactPerson.userRole']);

            Mail::to($user)->queue(new InquiryReceivedMail($inquiry));

            DB::afterCommit(function () use ($inquiry) {
                if ($inquiry->event) {
                    app(CroNotificationService::class)->notifyInquirySubmitted(
                        $inquiry->event,
                        $inquiry->id,
                        $inquiry->subject,
                    );
                }
            });

            return $inquiry;
        });
    }

    public function reply(Inquiry $inquiry, User $cro, string $message): InquiryResponse
    {
        if (trim($message) === '') {
            throw new RuntimeException('A reply message is required.');
        }

        $this->ensureCanHandle($inquiry, $cro);

        return DB::transaction(function () use ($inquiry, $cro, $message) {
            $fromAssignee = $inquiry->assigned_to;

            $response = InquiryResponse::create([
                'inquiry_id' => $inquiry->id,
                'user_id' => $cro->id,
                'message' => $message,
            ]);

            $payload = [];
            if ((int) $fromAssignee !== (int) $cro->id) {
                $payload['assigned_to'] = $cro->id;
            }
            if ($inquiry->status === SupportTicketStatusEnum::Open) {
                $payload['status'] = SupportTicketStatusEnum::InProgress;
                $this->auditService->logStatusChange(
                    'inquiry',
                    $inquiry->id,
                    SupportTicketStatusEnum::Open->value,
                    SupportTicketStatusEnum::InProgress->value,
                );
            }

            if ($payload !== []) {
                $inquiry->update($payload);
                if (array_key_exists('assigned_to', $payload)) {
                    $this->auditService->logAssignmentChange('inquiry', $inquiry->id, $fromAssignee, $cro->id);
                }
            }

            $this->auditService->logReply('inquiry', $inquiry->id, $message);

            DB::afterCommit(function () use ($inquiry) {
                $inquiry->load(['user', 'event']);
                Mail::to($inquiry->user)->queue(new InquiryAnsweredMail($inquiry));
                app(AttendeeNotificationService::class)->send(
                    $inquiry->user,
                    AttendeeNotificationCategory::Interaction,
                    'inquiry_replied',
                    'A CRO replied to your inquiry: "'.$inquiry->subject.'".',
                    route('notifications.index', ['category' => AttendeeNotificationCategory::Interaction->value]),
                    [
                        'inquiry_id' => $inquiry->id,
                        'event_id' => $inquiry->event_id,
                    ],
                );
            });

            return $response;
        });
    }

    public function updateStatus(Inquiry $inquiry, User $cro, SupportTicketStatusEnum $status): void
    {
        $this->ensureCanHandle($inquiry, $cro);

        $oldStatus = $inquiry->status;

        if ($oldStatus === $status) {
            return;
        }

        $becameResolved = $this->isResolvedStatus($status) && ! $this->isResolvedStatus($oldStatus);

        $inquiry->update([
            'status' => $status,
            'assigned_to' => $cro->id,
        ]);

        $this->auditService->logStatusChange('inquiry', $inquiry->id, $oldStatus->value, $status->value);

        if ($becameResolved) {
            $inquiry->loadMissing(['user', 'event']);
            app(AttendeeNotificationService::class)->send(
                $inquiry->user,
                AttendeeNotificationCategory::Interaction,
                'inquiry_resolved',
                'Your inquiry "'.$inquiry->subject.'" has been '.$status->label().'.',
                route('notifications.index', ['category' => AttendeeNotificationCategory::Interaction->value]),
                [
                    'inquiry_id' => $inquiry->id,
                    'event_id' => $inquiry->event_id,
                ],
            );
        }
    }

    private function isResolvedStatus(SupportTicketStatusEnum $status): bool
    {
        return in_array($status, [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed], true);
    }

    public function claim(Inquiry $inquiry, User $cro): void
    {
        throw new RuntimeException('Inquiries belong to the assigned event CRO and do not need to be claimed.');
    }

    public function reassign(Inquiry $inquiry, User $actor, ?User $assignee): void
    {
        throw new RuntimeException('Inquiries stay with the assigned event CRO and cannot be reassigned.');
    }

    public function updateInternalNotes(Inquiry $inquiry, User $cro, ?string $notes): void
    {
        $this->ensureCanHandle($inquiry, $cro);

        $inquiry->update([
            'internal_notes' => filled($notes) ? trim($notes) : null,
            'assigned_to' => $cro->id,
        ]);
    }

    /**
     * Move open / in-progress inquiries to the new event CRO.
     * Resolved and closed cases keep their historical owner.
     */
    public function transferOpenCasesToEventCro(Event $event, int $toCroId): void
    {
        $openStatuses = [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress];

        Inquiry::query()
            ->where('event_id', $event->id)
            ->whereIn('status', $openStatuses)
            ->where(function ($query) use ($toCroId) {
                $query->whereNull('assigned_to')
                    ->orWhere('assigned_to', '!=', $toCroId);
            })
            ->get()
            ->each(function (Inquiry $inquiry) use ($toCroId) {
                $from = $inquiry->assigned_to;
                $inquiry->update(['assigned_to' => $toCroId]);
                $this->auditService->logAssignmentChange('inquiry', $inquiry->id, $from, $toCroId);
            });
    }

    /**
     * Only the CRO assigned to the inquiry's event may handle it.
     */
    private function ensureCanHandle(Inquiry $inquiry, User $cro): void
    {
        if ($inquiry->isInCroQueue((int) $cro->id)) {
            return;
        }

        throw new RuntimeException('This inquiry belongs to the assigned event CRO.');
    }
}
