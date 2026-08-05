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
        $inquiry = Inquiry::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'subject' => $subject,
            'message' => $message,
            'status' => SupportTicketStatusEnum::Open,
        ]);

        $inquiry->load(['user', 'event']);

        Mail::to($user)->queue(new InquiryReceivedMail($inquiry));

        if ($inquiry->event) {
            app(CroNotificationService::class)->notifyInquirySubmitted(
                $inquiry->event,
                $inquiry->id,
                $inquiry->subject,
            );
        }

        return $inquiry;
    }

    public function reply(Inquiry $inquiry, User $cro, string $message): InquiryResponse
    {
        if (trim($message) === '') {
            throw new RuntimeException('A reply message is required.');
        }

        return DB::transaction(function () use ($inquiry, $cro, $message) {
            $response = InquiryResponse::create([
                'inquiry_id' => $inquiry->id,
                'user_id' => $cro->id,
                'message' => $message,
            ]);

            if ($inquiry->status === SupportTicketStatusEnum::Open) {
                $oldStatus = $inquiry->status->value;
                $inquiry->update([
                    'status' => SupportTicketStatusEnum::InProgress,
                    'assigned_to' => $cro->id,
                ]);
                $this->auditService->logStatusChange('inquiry', $inquiry->id, $oldStatus, SupportTicketStatusEnum::InProgress->value);
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
        $oldStatus = $inquiry->status->value;

        if ($oldStatus === $status->value) {
            return;
        }

        $inquiry->update([
            'status' => $status,
            'assigned_to' => $cro->id,
        ]);

        $this->auditService->logStatusChange('inquiry', $inquiry->id, $oldStatus, $status->value);
    }
}
