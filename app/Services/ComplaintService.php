<?php

namespace App\Services;

use App\Enums\AttendeeNotificationCategory;
use App\Enums\SupportTicketStatusEnum;
use App\Mail\ComplaintAnsweredMail;
use App\Mail\ComplaintReceivedMail;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintResponse;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ComplaintService
{
    public function __construct(
        protected SupportAuditService $auditService,
    ) {}

    public function submit(User $user, string $subject, string $message, array $files = [], ?int $eventId = null): Complaint
    {
        return DB::transaction(function () use ($user, $subject, $message, $files, $eventId) {
            $complaint = Complaint::create([
                'user_id' => $user->id,
                'event_id' => $eventId,
                'subject' => $subject,
                'message' => $message,
                'status' => SupportTicketStatusEnum::Open,
            ]);

            foreach ($files as $file) {
                $this->storeAttachment($complaint, $file);
            }

            $complaint->load(['user', 'event', 'attachments']);

            Mail::to($user)->queue(new ComplaintReceivedMail($complaint));

            app(CroNotificationService::class)->notifyComplaintSubmitted($complaint);

            return $complaint;
        });
    }

    public function reply(Complaint $complaint, User $cro, string $message): ComplaintResponse
    {
        if (trim($message) === '') {
            throw new RuntimeException('A reply message is required.');
        }

        return DB::transaction(function () use ($complaint, $cro, $message) {
            $response = ComplaintResponse::create([
                'complaint_id' => $complaint->id,
                'user_id' => $cro->id,
                'message' => $message,
            ]);

            if ($complaint->status === SupportTicketStatusEnum::Open) {
                $oldStatus = $complaint->status->value;
                $complaint->update([
                    'status' => SupportTicketStatusEnum::InProgress,
                    'assigned_to' => $cro->id,
                ]);
                $this->auditService->logStatusChange('complaint', $complaint->id, $oldStatus, SupportTicketStatusEnum::InProgress->value);
            }

            $this->auditService->logReply('complaint', $complaint->id, $message);

            DB::afterCommit(function () use ($complaint) {
                $complaint->load(['user']);
                Mail::to($complaint->user)->queue(new ComplaintAnsweredMail($complaint));
                app(AttendeeNotificationService::class)->send(
                    $complaint->user,
                    AttendeeNotificationCategory::Interaction,
                    'complaint_replied',
                    'A CRO replied to your complaint: "'.$complaint->subject.'".',
                    route('notifications.index', ['category' => AttendeeNotificationCategory::Interaction->value]),
                    ['complaint_id' => $complaint->id],
                );
            });

            return $response;
        });
    }

    public function updateStatus(Complaint $complaint, User $cro, SupportTicketStatusEnum $status): void
    {
        $oldStatus = $complaint->status->value;

        if ($oldStatus === $status->value) {
            return;
        }

        $complaint->update([
            'status' => $status,
            'assigned_to' => $cro->id,
        ]);

        $this->auditService->logStatusChange('complaint', $complaint->id, $oldStatus, $status->value);
    }

    public function claim(Complaint $complaint, User $cro): void
    {
        if (! $complaint->isUnassigned() && ! $complaint->isAssignedTo($cro->id)) {
            throw new RuntimeException('This complaint is already claimed by another CRO. Use Reassign instead.');
        }

        if ($complaint->isAssignedTo($cro->id)) {
            return;
        }

        $from = $complaint->assigned_to;
        $payload = ['assigned_to' => $cro->id];

        if ($complaint->status === SupportTicketStatusEnum::Open) {
            $payload['status'] = SupportTicketStatusEnum::InProgress;
            $this->auditService->logStatusChange(
                'complaint',
                $complaint->id,
                SupportTicketStatusEnum::Open->value,
                SupportTicketStatusEnum::InProgress->value,
            );
        }

        $complaint->update($payload);
        $this->auditService->logAssignmentChange('complaint', $complaint->id, $from, $cro->id);
    }

    public function reassign(Complaint $complaint, User $actor, ?User $assignee): void
    {
        $toId = $assignee?->id;
        $fromId = $complaint->assigned_to;

        if ((int) $fromId === (int) $toId) {
            return;
        }

        if ($assignee && ! app(CroNotificationService::class)->isCro($assignee)) {
            throw new RuntimeException('You can only reassign to an active CRO.');
        }

        $complaint->update(['assigned_to' => $toId]);
        $this->auditService->logAssignmentChange('complaint', $complaint->id, $fromId, $toId);
    }

    public function updateInternalNotes(Complaint $complaint, User $cro, ?string $notes): void
    {
        $complaint->update([
            'internal_notes' => filled($notes) ? trim($notes) : null,
            'assigned_to' => $complaint->assigned_to ?? $cro->id,
        ]);
    }

    protected function storeAttachment(Complaint $complaint, UploadedFile $file): ComplaintAttachment
    {
        $directory = public_path('uploads/complaints');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileName = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move($directory, $fileName);

        return ComplaintAttachment::create([
            'complaint_id' => $complaint->id,
            'file_path' => 'uploads/complaints/'.$fileName,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);
    }
}
