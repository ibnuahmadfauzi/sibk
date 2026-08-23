<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\BkCase;
use App\Models\Consultation;
use App\Models\Correction;
use App\Models\ExternalSyncRun;
use App\Models\FollowUp;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CorrectionService
{
    public function __construct(
        private readonly CaseService $caseService,
        private readonly FollowUpService $followUpService,
        private readonly ConsultationService $consultationService,
        private readonly AchievementService $achievementService,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    /** @param array{target_type: string, target_id: int, field_name: string, proposed_value?: string|null, reason: string} $data */
    public function submit(array $data, User $actor): Correction
    {
        return DB::transaction(function () use ($data, $actor): Correction {
            $target = $this->resolveTarget($data['target_type'], $data['target_id'], true);
            $type = $target instanceof Student ? Correction::TYPE_MASTER : Correction::TYPE_OPERATIONAL;
            $this->validateSubmissionAccess($target, $type, $actor);
            $definition = $this->fieldDefinition($data['target_type'], $data['field_name']);
            [$oldValue, $oldLabel] = $this->currentValue($target, $data['field_name'], $definition);
            [$proposedValue, $proposedLabel] = $this->normalizeProposedValue($data['proposed_value'] ?? null, $definition);

            if ($oldValue === $proposedValue) {
                throw ValidationException::withMessages(['proposed_value' => 'Nilai usulan harus berbeda dari nilai saat ini.']);
            }

            $status = $this->status('menunggu');
            $correction = Correction::query()->create([
                'correction_type' => $type,
                'target_type' => $target->getMorphClass(),
                'target_id' => $target->getKey(),
                'target_label' => $this->targetLabel($target),
                'field_name' => $data['field_name'],
                'field_label' => $definition['label'],
                'old_value' => $oldValue,
                'proposed_value' => $proposedValue,
                'old_value_label' => $oldLabel,
                'proposed_value_label' => $proposedLabel,
                'reason' => trim($data['reason']),
                'status_id' => $status->getKey(),
                'requester_id' => $actor->getKey(),
            ]);
            $correction->update([
                'registration_number' => sprintf('KR-%s-%04d', now()->format('Y'), $correction->getKey()),
            ]);

            $this->auditService->record(
                action: 'correction.submitted',
                auditable: $correction,
                summary: sprintf('Pengajuan %s untuk %s dibuat.', $correction->registration_number, $correction->target_label),
                actor: $actor,
                after: $this->auditSnapshot($correction),
            );
            $recipients = $this->notificationService->activeUsersWithRole(
                $type === Correction::TYPE_MASTER ? 'admin_it' : 'koordinator_bk',
            );
            $this->notificationService->send(
                recipients: $recipients,
                category: UserNotification::CATEGORY_CORRECTION,
                title: sprintf('Pengajuan koreksi %s diterima.', $correction->registration_number),
                message: sprintf('%s memerlukan tindak lanjut sesuai fungsi Anda.', $correction->target_label),
                target: $correction,
                actionRoute: 'corrections.show',
                actionParameters: ['correction' => $correction->getKey()],
                deduplicationKey: 'correction-submitted:'.$correction->getKey(),
            );

            return $correction->load(['status', 'requester']);
        });
    }

    /** @param array{decision: string, review_notes?: string|null} $data */
    public function verifyOperational(Correction $correction, array $data, User $coordinator): Correction
    {
        return DB::transaction(function () use ($correction, $data, $coordinator): Correction {
            if (! $coordinator->hasRole('koordinator_bk')) {
                abort(403);
            }
            $correction = Correction::query()->with('status')->lockForUpdate()->findOrFail($correction->getKey());
            if ($correction->correction_type !== Correction::TYPE_OPERATIONAL || $correction->status->code !== 'menunggu') {
                throw ValidationException::withMessages(['correction' => 'Pengajuan operasional ini tidak dapat diverifikasi lagi.']);
            }

            $decision = $data['decision'];
            if ($decision === 'approved') {
                $target = $this->resolveStoredTarget($correction, true);
                $definition = $this->definitionForCorrection($correction);
                [$currentValue] = $this->currentValue($target, $correction->field_name, $definition);
                if ($currentValue !== $correction->old_value) {
                    throw ValidationException::withMessages([
                        'correction' => 'Nilai objek telah berubah sejak pengajuan dibuat. Periksa dan ajukan koreksi baru.',
                    ]);
                }
                $this->applyOperational($target, $correction->field_name, $correction->proposed_value, $coordinator);
            }

            $statusCode = match ($decision) {
                'approved' => 'disetujui',
                'rejected' => 'ditolak',
                'revision_requested' => 'perlu_perbaikan',
                default => throw ValidationException::withMessages(['decision' => 'Keputusan verifikasi tidak tersedia.']),
            };
            $before = $this->auditSnapshot($correction);
            $correction->update([
                'status_id' => $this->status($statusCode)->getKey(),
                'reviewer_id' => $coordinator->getKey(),
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_at' => now(),
                'processed_at' => $decision === 'approved' ? now() : null,
            ]);
            $this->auditService->record(
                action: 'correction.reviewed',
                auditable: $correction,
                summary: sprintf('Pengajuan %s diverifikasi dengan hasil %s.', $correction->registration_number, $statusCode),
                actor: $coordinator,
                before: $before,
                after: $this->auditSnapshot($correction->refresh()),
            );
            $correction->loadMissing('requester');
            $this->notificationService->send(
                recipients: collect([$correction->requester]),
                category: UserNotification::CATEGORY_CORRECTION,
                title: sprintf('Koreksi %s telah diperiksa.', $correction->registration_number),
                message: sprintf('Hasil pemeriksaan: %s.', $statusCode),
                target: $correction,
                actionRoute: 'corrections.show',
                actionParameters: ['correction' => $correction->getKey()],
                deduplicationKey: 'correction-reviewed:'.$correction->getKey(),
            );

            return $correction->load(['status', 'requester', 'reviewer']);
        });
    }

    /** @param array{action: string, review_notes?: string|null, external_sync_run_id?: int|null} $data */
    public function processMaster(Correction $correction, array $data, User $admin): Correction
    {
        return DB::transaction(function () use ($correction, $data, $admin): Correction {
            if (! $admin->hasRole('admin_it')) {
                abort(403);
            }
            $correction = Correction::query()->with('status')->lockForUpdate()->findOrFail($correction->getKey());
            if ($correction->correction_type !== Correction::TYPE_MASTER
                || ! in_array($correction->status->code, ['menunggu', 'diproses'], true)) {
                throw ValidationException::withMessages(['correction' => 'Laporan koreksi master ini tidak dapat diproses lagi.']);
            }

            $action = $data['action'];
            $syncRun = null;
            if ($action === 'completed') {
                $syncRun = ExternalSyncRun::query()->findOrFail((int) ($data['external_sync_run_id'] ?? 0));
                if ($syncRun->source !== 'dapodik'
                    || ! in_array($syncRun->status, [ExternalSyncRun::STATUS_SUCCEEDED, ExternalSyncRun::STATUS_WARNING], true)
                    || $syncRun->finished_at === null
                    || $syncRun->finished_at->lt($correction->created_at)) {
                    throw ValidationException::withMessages(['external_sync_run_id' => 'Pilih sinkronisasi Dapodik berhasil yang dijalankan setelah pengajuan dibuat.']);
                }
                $target = $this->resolveStoredTarget($correction, true);
                [$currentValue] = $this->currentValue($target, $correction->field_name, $this->definitionForCorrection($correction));
                if (mb_strtolower((string) $currentValue) !== mb_strtolower((string) $correction->proposed_value)) {
                    throw ValidationException::withMessages(['external_sync_run_id' => 'Hasil sinkronisasi belum sesuai nilai usulan koreksi master.']);
                }
            }

            $statusCode = match ($action) {
                'processing' => 'diproses',
                'completed' => 'selesai',
                'rejected' => 'ditolak',
                default => throw ValidationException::withMessages(['action' => 'Tindakan pemrosesan master tidak tersedia.']),
            };
            $before = $this->auditSnapshot($correction);
            $correction->update([
                'status_id' => $this->status($statusCode)->getKey(),
                'reviewer_id' => $admin->getKey(),
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_at' => now(),
                'processed_at' => $action === 'completed' ? now() : null,
                'external_sync_run_id' => $syncRun?->getKey(),
            ]);
            $this->auditService->record(
                action: 'correction.master_'.$action,
                auditable: $correction,
                summary: sprintf('Laporan koreksi master %s berstatus %s.', $correction->registration_number, $statusCode),
                actor: $admin,
                before: $before,
                after: $this->auditSnapshot($correction->refresh()),
            );
            $correction->loadMissing('requester');
            $this->notificationService->send(
                recipients: collect([$correction->requester]),
                category: UserNotification::CATEGORY_CORRECTION,
                title: sprintf('Koreksi master %s diperbarui.', $correction->registration_number),
                message: sprintf('Status pemrosesan sumber resmi: %s.', $statusCode),
                target: $correction,
                actionRoute: 'corrections.show',
                actionParameters: ['correction' => $correction->getKey()],
                deduplicationKey: 'correction-master:'.$correction->getKey().':'.$statusCode,
            );

            return $correction->load(['status', 'requester', 'reviewer', 'externalSyncRun']);
        });
    }

    /** @return array<string, array<string, array{label: string, type: string, category?: string, nullable?: bool}>> */
    public function fieldDefinitions(): array
    {
        return [
            'case' => [
                'service_date' => ['label' => 'Tanggal Layanan', 'type' => 'date'],
                'service_field_id' => ['label' => 'Bidang Layanan', 'type' => 'reference', 'category' => 'service_field'],
                'initial_action' => ['label' => 'Penanganan Awal', 'type' => 'text'],
            ],
            'follow_up' => [
                'planned_date' => ['label' => 'Tanggal Rencana', 'type' => 'date'],
                'execution_date' => ['label' => 'Tanggal Pelaksanaan', 'type' => 'date', 'nullable' => true],
                'follow_up_type_id' => ['label' => 'Bentuk Tindak Lanjut', 'type' => 'reference', 'category' => 'follow_up_type'],
                'status_id' => ['label' => 'Status Tindak Lanjut', 'type' => 'reference', 'category' => 'follow_up_status'],
                'result' => ['label' => 'Hasil Tindak Lanjut', 'type' => 'text', 'nullable' => true],
                'next_plan' => ['label' => 'Rencana Berikutnya', 'type' => 'text', 'nullable' => true],
            ],
            'consultation' => [
                'session_date' => ['label' => 'Tanggal Sesi', 'type' => 'date'],
                'service_field_id' => ['label' => 'Bidang Layanan', 'type' => 'reference', 'category' => 'service_field'],
                'status_id' => ['label' => 'Status Konsultasi', 'type' => 'reference', 'category' => 'consultation_status'],
                'topic' => ['label' => 'Topik', 'type' => 'text'],
                'referral_source' => ['label' => 'Sumber Rujukan', 'type' => 'text', 'nullable' => true],
                'starts_at' => ['label' => 'Jam Mulai', 'type' => 'time', 'nullable' => true],
                'ends_at' => ['label' => 'Jam Selesai', 'type' => 'time', 'nullable' => true],
                'follow_up_date' => ['label' => 'Jadwal Tindak Lanjut', 'type' => 'date', 'nullable' => true],
                'general_summary' => ['label' => 'Ringkasan Umum', 'type' => 'text', 'nullable' => true],
            ],
            'achievement' => [
                'type_id' => ['label' => 'Jenis Prestasi', 'type' => 'reference', 'category' => 'achievement_type'],
                'level_id' => ['label' => 'Tingkat Prestasi', 'type' => 'reference', 'category' => 'achievement_level'],
                'activity_name' => ['label' => 'Nama Kegiatan', 'type' => 'text'],
                'organizer' => ['label' => 'Penyelenggara', 'type' => 'text'],
                'achievement_date' => ['label' => 'Tanggal Prestasi', 'type' => 'date'],
                'result' => ['label' => 'Hasil atau Peringkat', 'type' => 'text'],
                'evidence_reference' => ['label' => 'Referensi Bukti', 'type' => 'text'],
                'evidence_description' => ['label' => 'Keterangan Bukti', 'type' => 'text', 'nullable' => true],
                'notes' => ['label' => 'Catatan Prestasi', 'type' => 'text', 'nullable' => true],
            ],
            'student' => [
                'name' => ['label' => 'Nama Murid', 'type' => 'text'],
                'nisn' => ['label' => 'NISN', 'type' => 'nisn'],
                'classroom' => ['label' => 'Rombel / Kelas', 'type' => 'text'],
            ],
        ];
    }

    private function validateSubmissionAccess(Model $target, string $type, User $actor): void
    {
        if ($type === Correction::TYPE_OPERATIONAL) {
            if (! $actor->hasRole('guru_bk')) {
                throw ValidationException::withMessages(['target_type' => 'Koreksi operasional hanya dapat diajukan melalui fungsi Guru BK.']);
            }
            $object = $target instanceof FollowUp ? $target->case : $target;
            if (! $actor->can('view', $object)) {
                throw ValidationException::withMessages(['target_id' => 'Objek operasional tidak berada dalam kewenangan Anda.']);
            }
            if ($target instanceof Achievement && $target->verificationStatus()->first()?->code !== 'terverifikasi') {
                throw ValidationException::withMessages(['target_id' => 'Koreksi hanya dapat diajukan untuk prestasi yang sudah terverifikasi.']);
            }

            return;
        }

        if (! $target instanceof Student || ! $actor->hasAnyRole(['guru_bk', 'koordinator_bk']) || ! $actor->can('view', $target)) {
            throw ValidationException::withMessages(['target_id' => 'Murid tidak berada dalam kewenangan pelaporan Anda.']);
        }
    }

    private function resolveTarget(string $type, int $id, bool $lock = false): Model
    {
        $class = match ($type) {
            'case' => BkCase::class,
            'follow_up' => FollowUp::class,
            'consultation' => Consultation::class,
            'achievement' => Achievement::class,
            'student' => Student::class,
            default => throw ValidationException::withMessages(['target_type' => 'Jenis objek koreksi tidak tersedia.']),
        };
        $query = $class::query();
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function resolveStoredTarget(Correction $correction, bool $lock = false): Model
    {
        $type = array_search($correction->target_type, [
            'case' => BkCase::class,
            'follow_up' => FollowUp::class,
            'consultation' => Consultation::class,
            'achievement' => Achievement::class,
            'student' => Student::class,
        ], true);

        return $this->resolveTarget((string) $type, (int) $correction->target_id, $lock);
    }

    /** @return array{label: string, type: string, category?: string, nullable?: bool} */
    private function fieldDefinition(string $targetType, string $field): array
    {
        $definition = $this->fieldDefinitions()[$targetType][$field] ?? null;
        if ($definition === null) {
            throw ValidationException::withMessages(['field_name' => 'Atribut tidak tersedia untuk jenis objek yang dipilih.']);
        }

        return $definition;
    }

    /** @return array{label: string, type: string, category?: string, nullable?: bool} */
    private function definitionForCorrection(Correction $correction): array
    {
        $targetType = match ($correction->target_type) {
            BkCase::class => 'case',
            FollowUp::class => 'follow_up',
            Consultation::class => 'consultation',
            Achievement::class => 'achievement',
            Student::class => 'student',
            default => '',
        };

        return $this->fieldDefinition($targetType, $correction->field_name);
    }

    /** @param array{label: string, type: string, category?: string, nullable?: bool} $definition @return array{?string, ?string} */
    private function currentValue(Model $target, string $field, array $definition): array
    {
        if ($target instanceof Student && $field === 'classroom') {
            $membership = $target->classMemberships()->active()->effectiveOn(now()->toDateString())->with('classroom')->latest('effective_from')->first();
            $value = $membership?->classroom?->name;

            return [$value, $value];
        }

        $value = $target->{$field};
        if ($value instanceof \DateTimeInterface) {
            $value = $definition['type'] === 'time' ? $value->format('H:i') : $value->format('Y-m-d');
        } elseif ($definition['type'] === 'time' && $value !== null) {
            $value = substr((string) $value, 0, 5);
        } elseif ($value !== null) {
            $value = (string) $value;
        }

        if ($definition['type'] === 'reference' && $value !== null) {
            $reference = ReferenceValue::query()->find((int) $value);

            return [$value, $reference?->label ?? $value];
        }

        return [$value, $value];
    }

    /** @param array{label: string, type: string, category?: string, nullable?: bool} $definition @return array{?string, ?string} */
    private function normalizeProposedValue(?string $value, array $definition): array
    {
        $value = $value === null ? null : trim($value);
        if (($value === null || $value === '') && ! ($definition['nullable'] ?? false)) {
            throw ValidationException::withMessages(['proposed_value' => 'Nilai usulan wajib diisi.']);
        }
        if ($value === null || $value === '') {
            return [null, null];
        }

        if ($definition['type'] === 'reference') {
            $reference = ReferenceValue::query()->active()->forCategory((string) $definition['category'])
                ->where(function ($query) use ($value): void {
                    $query->where('code', $value)->orWhere('label', $value);
                    if (ctype_digit($value)) {
                        $query->orWhereKey((int) $value);
                    }
                })->first();
            if ($reference === null) {
                throw ValidationException::withMessages(['proposed_value' => 'Nilai referensi usulan tidak tersedia.']);
            }

            return [(string) $reference->getKey(), $reference->label];
        }

        if ($definition['type'] === 'date') {
            try {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
            } catch (\Throwable) {
                $date = null;
            }
            if ($date === null || $date->format('Y-m-d') !== $value) {
                throw ValidationException::withMessages(['proposed_value' => 'Nilai usulan harus menggunakan format tanggal YYYY-MM-DD.']);
            }
        }
        if ($definition['type'] === 'time' && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) !== 1) {
            throw ValidationException::withMessages(['proposed_value' => 'Nilai usulan harus menggunakan format jam HH:MM.']);
        }
        if ($definition['type'] === 'nisn' && preg_match('/^[0-9]{4,20}$/', $value) !== 1) {
            throw ValidationException::withMessages(['proposed_value' => 'NISN usulan hanya boleh berisi 4–20 angka.']);
        }

        return [$value, $value];
    }

    private function targetLabel(Model $target): string
    {
        return match (true) {
            $target instanceof BkCase => 'Kasus '.$target->registration_number,
            $target instanceof FollowUp => 'Tindak lanjut kasus '.$target->case?->registration_number,
            $target instanceof Consultation => 'Konsultasi '.$target->registration_number,
            $target instanceof Achievement => 'Prestasi '.$target->activity_name,
            $target instanceof Student => sprintf('Murid %s (%s)', $target->name, $target->nisn),
            default => class_basename($target).' #'.$target->getKey(),
        };
    }

    private function applyOperational(Model $target, string $field, ?string $value, User $coordinator): void
    {
        match (true) {
            $target instanceof BkCase => $this->caseService->applyApprovedCorrection($target, $field, $value, $coordinator),
            $target instanceof FollowUp => $this->followUpService->applyApprovedCorrection($target, $field, $value, $coordinator),
            $target instanceof Consultation => $this->consultationService->applyApprovedCorrection($target, $field, $value, $coordinator),
            $target instanceof Achievement => $this->achievementService->applyApprovedCorrection($target, $field, $value, $coordinator),
            default => throw ValidationException::withMessages(['correction' => 'Objek operasional tidak didukung.']),
        };
    }

    private function status(string $code): ReferenceValue
    {
        return ReferenceValue::query()->active()->forCategory('correction_status')->where('code', $code)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function auditSnapshot(Correction $correction): array
    {
        return [
            'registration_number' => $correction->registration_number,
            'correction_type' => $correction->correction_type,
            'target_type' => $correction->target_type,
            'target_id' => $correction->target_id,
            'field_name' => $correction->field_name,
            'status_id' => $correction->status_id,
            'requester_id' => $correction->requester_id,
            'reviewer_id' => $correction->reviewer_id,
            'external_sync_run_id' => $correction->external_sync_run_id,
        ];
    }
}
