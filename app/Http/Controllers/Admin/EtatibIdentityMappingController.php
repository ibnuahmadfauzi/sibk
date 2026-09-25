<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MapEtatibIdentityRequest;
use App\Http\Requests\Admin\RevokeEtatibIdentityMappingRequest;
use App\Models\EtatibIdentityMapping;
use App\Models\ExternalSyncIssue;
use App\Models\ExternalTatibRecord;
use App\Models\Student;
use App\Models\User;
use App\Services\EtatibIdentityMappingService;
use App\Services\EtatibIdentityNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

final class EtatibIdentityMappingController extends Controller
{
    public function index(Request $request, EtatibIdentityNormalizer $normalizer): Response
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');

        $issues = ExternalSyncIssue::query()
            ->where('entity_type', 'etatib_record')
            ->whereIn('issue_code', ['student_not_found', 'student_name_mismatch'])
            ->whereNull('resolved_at')
            ->latest('id')
            ->get();
        $records = ExternalTatibRecord::query()
            ->whereIn('source_identifier', $issues->pluck('source_identifier')->filter())
            ->get()
            ->keyBy('source_identifier');
        $identityRecords = ExternalTatibRecord::query()
            ->whereIn('nisn', $records->pluck('nisn')->unique())
            ->get()
            ->groupBy(fn (ExternalTatibRecord $record): string => $record->source_student_name === null
                ? $record->nisn.'|'
                : $normalizer->key($record->nisn, $record->source_student_name));

        $groups = [];
        foreach ($issues as $issue) {
            $record = $records->get($issue->source_identifier);
            if (! $record instanceof ExternalTatibRecord || $record->source_student_name === null) {
                continue;
            }

            $key = $normalizer->key($record->nisn, $record->source_student_name);
            if (isset($groups[$key])) {
                $groups[$key]['reasons'][] = $issue->summary;

                continue;
            }

            $matchingRecords = $identityRecords->get($key, collect());
            $groups[$key] = [
                'issue_id' => $issue->getKey(),
                'nisn' => $record->nisn,
                'name' => $record->source_student_name,
                'classroom' => $record->source_classroom_name,
                'reasons' => [$issue->summary],
                'record_count' => $matchingRecords->count(),
            ];
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = 20;
        $groupCollection = collect($groups)->sortBy([
            ['name', 'asc'],
            ['nisn', 'asc'],
        ])->values();
        $conflicts = new LengthAwarePaginator(
            $groupCollection->forPage($page, $perPage)->values(),
            $groupCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return response()->view('pages.data-master.etatib-conflicts', [
            'tab' => $request->string('tab')->toString() === 'mappings' ? 'mappings' : 'conflicts',
            'conflicts' => $conflicts,
            'mappings' => EtatibIdentityMapping::query()
                ->active()
                ->with(['student', 'mapper'])
                ->latest('mapped_at')
                ->paginate(20, ['*'], 'mapping_page')
                ->withQueryString(),
        ])->header('Cache-Control', 'no-store');
    }

    public function candidates(Request $request): JsonResponse
    {
        Gate::forUser($request->user())->authorize('manageDataMaster');
        $search = $request->string('search')->trim()->toString();
        if (mb_strlen($search) < 2) {
            return response()->json(['data' => []])->header('Cache-Control', 'no-store, private');
        }

        $students = Student::query()
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nisn', 'like', '%'.$search.'%')
                    ->orWhereHas('classMemberships.classroom', fn ($classrooms) => $classrooms
                        ->where('name', 'like', '%'.$search.'%'));
            })
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->with(['classroom', 'academicYear'])
                ->latestYearFirst()])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function (Student $student): array {
                $membership = $student->classMemberships->first();

                return [
                    'id' => $student->getKey(),
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'classroom' => $membership?->classroom?->name ?? '-',
                    'academic_year' => $membership?->academicYear?->name ?? '-',
                    'status' => $student->is_active ? 'Aktif' : 'Historis',
                    'source' => $this->sourceLabel($membership?->master_source ?? $student->master_source),
                ];
            });

        return response()->json(['data' => $students])->header('Cache-Control', 'no-store, private');
    }

    public function store(
        MapEtatibIdentityRequest $request,
        ExternalSyncIssue $issue,
        EtatibIdentityMappingService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $student = Student::query()->findOrFail($request->integer('student_id'));
        $service->mapFromIssue($issue, $student, $actor);

        return redirect()
            ->route('data-master.etatib.conflicts.index')
            ->with('success', 'Konflik e-Tatib berhasil dicocokkan.');
    }

    public function update(
        MapEtatibIdentityRequest $request,
        EtatibIdentityMapping $mapping,
        EtatibIdentityMappingService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $student = Student::query()->findOrFail($request->integer('student_id'));
        $service->change($mapping, $student, $actor);

        return redirect()
            ->route('data-master.etatib.conflicts.index', ['tab' => 'mappings'])
            ->with('success', 'Pencocokan manual e-Tatib berhasil diubah.');
    }

    public function destroy(
        RevokeEtatibIdentityMappingRequest $request,
        EtatibIdentityMapping $mapping,
        EtatibIdentityMappingService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->revoke($mapping, $actor);

        return redirect()
            ->route('data-master.etatib.conflicts.index', ['tab' => 'mappings'])
            ->with('success', 'Pencocokan manual dibatalkan. Aturan otomatis telah diterapkan kembali.');
    }

    private function sourceLabel(?string $source): string
    {
        return match ($source) {
            Student::MASTER_SOURCE_SCHOOL_PROVISIONAL => 'API Siswa',
            Student::MASTER_SOURCE_DAPODIK => 'Sumber resmi',
            default => 'Data lama',
        };
    }
}
