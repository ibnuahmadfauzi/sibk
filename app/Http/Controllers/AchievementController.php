<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AchievementIndexRequest;
use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Http\Requests\VerifyAchievementRequest;
use App\Models\Achievement;
use App\Models\Classroom;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(AchievementIndexRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $query = Achievement::query()->accessibleTo($user)->with(['student', 'type', 'level', 'verificationStatus', 'recorder']);
        $search = trim((string) ($filters['search'] ?? ''));
        $query->when($search, fn (Builder $achievements): Builder => $achievements->where(function (Builder $filter) use ($search): void {
            $filter->where('activity_name', 'like', '%'.$search.'%')
                ->orWhere('organizer', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn (Builder $students): Builder => $students
                    ->where('name', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%'));
        }));
        $query->when($filters['student_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('student_id', $id));
        $query->when($filters['type_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('type_id', $id));
        $query->when($filters['level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));
        $query->when($filters['status_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('verification_status_id', $id));
        $query->when($filters['date_start'] ?? null, fn (Builder $items, string $date): Builder => $items->whereDate('achievement_date', '>=', $date));
        $query->when($filters['date_end'] ?? null, fn (Builder $items, string $date): Builder => $items->whereDate('achievement_date', '<=', $date));
        $query->when($filters['classroom_id'] ?? null, fn (Builder $items, int $id): Builder => $items->whereHas('student.classMemberships', fn (Builder $memberships): Builder => $memberships
            ->where('classroom_id', $id)
            ->whereColumn('effective_from', '<=', 'achievements.achievement_date')
            ->where(function (Builder $period): void {
                $period->whereNull('effective_until')->orWhereColumn('effective_until', '>=', 'achievements.achievement_date');
            })));

        return view('pages.achievements.index', [
            'achievements' => $query->latest('achievement_date')->latest('id')->paginate(20)->withQueryString(),
            ...$this->options($user),
            'canCreateAchievement' => $user->can('create', Achievement::class),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', Achievement::class), 403);

        return $this->form($user, null, $request);
    }

    public function store(StoreAchievementRequest $request, AchievementService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $achievement = $service->create($request->validated(), $actor);

        return redirect()->route('achievements.show', $achievement)->with('success', 'Prestasi berhasil dicatat dan menunggu verifikasi.');
    }

    public function show(Request $request, Achievement $achievement): View
    {
        /** @var User $user */
        $user = $request->user();
        $achievement->load(['student.classMemberships.classroom.academicYear', 'type', 'level', 'verificationStatus', 'recorder', 'reviewer']);
        abort_unless($user->can('view', $achievement), 403);

        return view('pages.achievements.show', [
            'achievement' => $achievement,
            'canUpdateAchievement' => $user->can('update', $achievement),
            'canVerifyAchievement' => $user->can('verify', $achievement),
            'evidenceUrl' => $this->evidenceUrl($achievement->evidence_reference),
        ]);
    }

    public function edit(Request $request, Achievement $achievement): View
    {
        /** @var User $user */
        $user = $request->user();
        $achievement->load([
            'student.classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->effectiveOn(now()->toDateString())
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom'),
            'verificationStatus',
        ]);
        abort_unless($user->can('update', $achievement), 403);

        return $this->form($user, $achievement, $request);
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement, AchievementService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->update($achievement, $request->validated(), $actor);

        return redirect()->route('achievements.show', $achievement)->with('success', 'Prestasi berhasil diperbarui.');
    }

    public function verify(VerifyAchievementRequest $request, Achievement $achievement, AchievementService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->verify($achievement, $request->validated(), $actor);

        return redirect()->route('achievements.show', $achievement)->with('success', 'Keputusan verifikasi prestasi berhasil disimpan.');
    }

    private function form(User $user, ?Achievement $achievement, Request $request): View
    {
        return view('pages.achievements.create', [
            'achievement' => $achievement,
            'isEdit' => $achievement !== null,
            'students' => Student::query()->active()->professionallyAccessibleTo($user)
                ->with(['classMemberships' => fn ($memberships) => $memberships
                    ->active()
                    ->effectiveOn(now()->toDateString())
                    ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                    ->with('classroom')])
                ->orderBy('name')->get(),
            'types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
            'preselectedStudentId' => $request->integer('student_id') ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    private function options(User $user): array
    {
        return [
            'students' => Student::query()->active()->accessibleTo($user)->orderBy('name')->get(),
            'classrooms' => Classroom::query()->active()->whereHas('studentClassMemberships.student', fn (Builder $students): Builder => $students->accessibleTo($user))->orderBy('name')->get(),
            'types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
            'statuses' => ReferenceValue::query()->active()->forCategory('achievement_verification_status')->orderBy('sort_order')->get(),
        ];
    }

    private function evidenceUrl(string $reference): ?string
    {
        if (filter_var($reference, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $scheme = mb_strtolower((string) parse_url($reference, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $reference : null;
    }
}
