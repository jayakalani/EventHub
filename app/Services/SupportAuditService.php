<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class SupportAuditService
{
    public function logReply(string $type, int $ticketId, string $message): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => "CRO {$type} reply",
            'model_type' => $type === 'inquiry' ? 'App\\Models\\Inquiry' : 'App\\Models\\Complaint',
            'model_id' => $ticketId,
            'old_values' => null,
            'new_values' => json_encode(['message' => $message]),
            'ip_address' => request()->ip(),
        ]);
    }

    public function logStatusChange(string $type, int $ticketId, string $oldStatus, string $newStatus): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => "CRO {$type} status change",
            'model_type' => $type === 'inquiry' ? 'App\\Models\\Inquiry' : 'App\\Models\\Complaint',
            'model_id' => $ticketId,
            'old_values' => json_encode(['status' => $oldStatus]),
            'new_values' => json_encode(['status' => $newStatus]),
            'ip_address' => request()->ip(),
        ]);
    }
}
