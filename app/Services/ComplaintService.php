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

        $this->ensureCanHandle($complaint, $cro);

        return DB::transaction(function () use ($complaint, $cro, $message) {
            $wasUnassigned = $complaint->isUnassigned();

            $response = ComplaintResponse::create([
                'complaint_id' => $complaint->id,
                'user_id' => $cro->id,
                'message' => $message,
            ]);

            $payload = [];
            if ($wasUnassigned) {
                $payload['assigned_to'] = $cro->id;
            }
            if ($complaint->status === SupportTicketStatusEnum::Open) {
                $oldStatus = $complaint->status->value;
                $payload['status'] = SupportTicketStatusEnum::InProgress;
                $payload['assigned_to'] = $cro->id;
                $this->auditService->logStatusChange('complaint', $complaint->id, $oldStatus, SupportTicketStatusEnum::InProgress->value);
            }

            if ($payload !== []) {
                $fromAssignee = $complaint->assigned_to;
                $complaint->update($payload);
                if ($wasUnassigned || $fromAssignee === null) {
                    $this->auditService->logAssignmentChange('complaint', $complaint->id, $fromAssignee, $cro->id);
                }
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
        $this->ensureCanHandle($complaint, $cro);

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
        DB::transaction(function () use ($complaint, $cro) {
            $locked = Complaint::query()->whereKey($complaint->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isUnassigned() && ! $locked->isAssignedTo($cro->id)) {
                throw new RuntimeException('This complaint is already claimed by another CRO. Ask them to reassign it.');
            }

            if ($locked->isAssignedTo($cro->id)) {
                $complaint->refresh();

                return;
            }

            $from = $locked->assigned_to;
            $payload = ['assigned_to' => $cro->id];

            if ($locked->status === SupportTicketStatusEnum::Open) {
                $payload['status'] = SupportTicketStatusEnum::InProgress;
                $this->auditService->logStatusChange(
                    'complaint',
                    $locked->id,
                    SupportTicketStatusEnum::Open->value,
                    SupportTicketStatusEnum::InProgress->value,
                );
            }

            $locked->update($payload);
            $this->auditService->logAssignmentChange('complaint', $locked->id, $from, $cro->id);
            $complaint->refresh();
        });
    }

    public function reassign(Complaint $complaint, User $actor, ?User $assignee): void
    {
        $this->ensureCanReassign($complaint, $actor);

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
        $this->ensureCanHandle($complaint, $cro);

        $complaint->update([
            'internal_notes' => filled($notes) ? trim($notes) : null,
            'assigned_to' => $complaint->assigned_to ?? $cro->id,
        ]);
    }

    /**
     * Only the assignee (or anyone, if still unassigned) may handle the case.
     */
    private function ensureCanHandle(Complaint $complaint, User $cro): void
    {
        if ($complaint->isUnassigned() || $complaint->isAssignedTo($cro->id)) {
            return;
        }

        throw new RuntimeException('This complaint is claimed by another CRO. Ask them to reassign it, or wait until it is unassigned.');
    }

    /**
     * Only the current assignee may reassign. Unassigned cases may be assigned by any CRO in queue.
     */
    private function ensureCanReassign(Complaint $complaint, User $actor): void
    {
        if ($complaint->isUnassigned() || $complaint->isAssignedTo($actor->id)) {
            return;
        }

        throw new RuntimeException('Only the assigned CRO can reassign this complaint.');
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
