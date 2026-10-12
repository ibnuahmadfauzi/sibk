<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomCatalog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ClassroomCatalogService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function add(string $name, User $actor): ClassroomCatalog
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $name = $this->validName($name);

        return DB::transaction(function () use ($name, $actor): ClassroomCatalog {
            if (ClassroomCatalog::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                throw ValidationException::withMessages(['name' => 'Rombel sudah ada. Aktifkan kembali jika diperlukan.']);
            }
            $catalog = ClassroomCatalog::query()->create(['name' => $name, 'is_active' => true]);
            foreach ($this->openYears() as $year) {
                $classroom = Classroom::query()
                    ->where('academic_year_id', $year->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();
                if ($classroom === null) {
                    Classroom::query()->create([
                        'academic_year_id' => $year->id,
                        'classroom_catalog_id' => $catalog->id,
                        'name' => $name,
                        'is_active' => true,
                        'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                    ]);
                } else {
                    $classroom->update(['classroom_catalog_id' => $catalog->id, 'is_active' => true]);
                }
            }
            $this->auditService->record(
                action: 'classroom_catalog.created', auditable: $catalog,
                summary: 'Rombel ditambahkan ke Data Kelas.', actor: $actor,
                after: ['name' => $name, 'is_active' => true],
            );

            return $catalog;
        });
    }

    public function update(ClassroomCatalog $catalog, string $name, bool $active, User $actor): void
    {
        Gate::forUser($actor)->authorize('manageDataMaster');
        $name = $this->validName($name);

        DB::transaction(function () use ($catalog, $name, $active, $actor): void {
            $catalog = ClassroomCatalog::query()->lockForUpdate()->findOrFail($catalog->id);
            if (ClassroomCatalog::query()->whereKeyNot($catalog->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                throw ValidationException::withMessages(['name' => 'Nama rombel sudah digunakan.']);
            }
            $before = $catalog->only(['name', 'is_active']);
            $openYears = $this->openYears();
            $openYearIds = $openYears->pluck('id');
            $classrooms = Classroom::query()->where('classroom_catalog_id', $catalog->id)
                ->whereIn('academic_year_id', $openYearIds)->lockForUpdate()->get();
            foreach ($classrooms as $classroom) {
                if (Classroom::query()->where('academic_year_id', $classroom->academic_year_id)
                    ->whereKeyNot($classroom->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                    throw ValidationException::withMessages(['name' => 'Nama rombel sudah ada pada tahun ajaran berjalan.']);
                }
                if (! $active && ($classroom->studentClassMemberships()->exists()
                    || $classroom->teacherAssignments()->exists())) {
                    throw ValidationException::withMessages([
                        'is_active' => 'Rombel masih memiliki murid atau penugasan Guru BK. Selesaikan penempatannya lebih dulu.',
                    ]);
                }
            }
            $catalog->update(['name' => $name, 'is_active' => $active]);
            foreach ($classrooms as $classroom) {
                $classroom->update(['name' => $name, 'is_active' => $active]);
            }
            if ($active) {
                foreach ($openYears as $year) {
                    if ($classrooms->contains('academic_year_id', $year->id)) {
                        continue;
                    }
                    $existing = Classroom::query()->where('academic_year_id', $year->id)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
                    if ($existing !== null) {
                        throw ValidationException::withMessages(['name' => 'Nama rombel sudah ada pada tahun ajaran berjalan.']);
                    }
                    Classroom::query()->create([
                        'academic_year_id' => $year->id,
                        'classroom_catalog_id' => $catalog->id,
                        'name' => $name,
                        'is_active' => true,
                        'master_source' => Classroom::MASTER_SOURCE_SCHOOL_PROVISIONAL,
                    ]);
                }
            }
            $this->auditService->record(
                action: 'classroom_catalog.updated', auditable: $catalog,
                summary: 'Data rombel diperbarui.', actor: $actor,
                before: $before, after: $catalog->only(['name', 'is_active']),
            );
        });
    }

    private function validName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $name)) {
            throw ValidationException::withMessages(['name' => 'Nama rombel wajib diisi, maksimal 100 karakter.']);
        }

        return $name;
    }

    private function openYears(): \Illuminate\Database\Eloquent\Collection
    {
        return AcademicYear::query()->where(fn ($query) => $query
            ->where('is_active', true)->orWhereNull('activated_at'))->get();
    }
}
