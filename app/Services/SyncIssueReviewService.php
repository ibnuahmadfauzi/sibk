<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExternalSyncIssue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SyncIssueReviewService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function review(ExternalSyncIssue $issue, User $actor, string $status, string $note): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');

        DB::transaction(function () use ($issue, $actor, $status, $note): void {
            $locked = ExternalSyncIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
            if ($locked->resolved_at !== null) {
                throw ValidationException::withMessages(['status' => 'Masalah ini sudah selesai. Muat ulang daftar untuk melihat status terbaru.']);
            }

            $details = $locked->details ?? [];
            $previous = $details['review'] ?? null;
            $details['review'] = [
                'status' => $status,
                'note' => trim($note),
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now()->toIso8601String(),
            ];
            $locked->update(['details' => $details]);
            $this->auditService->record('sync_issue.reviewed', $locked, 'Admin memeriksa masalah sinkronisasi.', $actor,
                ['review' => $previous], ['review' => $details['review']]);
        });
    }
}
