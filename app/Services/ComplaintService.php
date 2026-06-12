<?php

namespace App\Services;

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

    public function submit(User $user, string $subject, string $message, array $files = []): Complaint
    {
        return DB::transaction(function () use ($user, $subject, $message, $files) {
            $complaint = Complaint::create([
                'user_id' => $user->id,
                'subject' => $subject,
                'message' => $message,
                'status' => SupportTicketStatusEnum::Open,
            ]);

            foreach ($files as $file) {
                $this->storeAttachment($complaint, $file);
            }

            $complaint->load(['user', 'attachments']);

            Mail::to($user)->queue(new ComplaintReceivedMail($complaint));

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
