<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AchievementIndexRequest;
use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Models\Achievement;
use App\Models\ReferenceValue;
use App\Models\Student;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(AchievementIndexRequest $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $filters = $request->validated();
        $query = Achievement::query()->accessibleTo($user)->with(['student', 'type', 'level', 'recorder']);
        $search = trim((string) ($filters['search'] ?? ''));
        $query->when($search, fn (Builder $achievements): Builder => $achievements->where(function (Builder $filter) use ($search): void {
            $filter->where('activity_name', 'like', '%'.$search.'%')
                ->orWhere('organizer', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn (Builder $students): Builder => $students
                    ->where('name', 'like', '%'.$search.'%')->orWhere('nisn', 'like', '%'.$search.'%'));
        }));
        $query->when($filters['level_id'] ?? null, fn (Builder $items, int $id): Builder => $items->where('level_id', $id));

        return view('pages.achievements.index', [
            'achievements' => $query->latest('achievement_date')->latest('id')->paginate(20)->withQueryString(),
            ...$this->options(),
            'canCreateAchievement' => $user->can('create', Achievement::class),
        ]);
    }

    public function create(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('create', Achievement::class), 403);

        return view('pages.achievements.create', $this->formData(null, $request));
    }

    public function store(StoreAchievementRequest $request, AchievementService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $achievement = $service->create($request->validated(), $actor);

        return redirect()->route('achievements.show', $achievement)->with('success', 'Prestasi berhasil dicatat.');
    }

    public function show(Request $request, Achievement $achievement): View
    {
        /** @var User $user */
        $user = $request->user();
        $achievement->load(['student.classMemberships.classroom.academicYear', 'type', 'level', 'recorder']);
        abort_unless($user->can('view', $achievement), 403);

        return view($request->boolean('modal') ? 'pages.achievements._detail-modal' : 'pages.achievements.show', [
            'achievement' => $achievement,
            'canUpdateAchievement' => $user->can('update', $achievement),
        ]);
    }

    public function edit(Request $request, Achievement $achievement): View
    {
        /** @var User $user */
        $user = $request->user();
        $achievement->load([
            'student.classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom'),
        ]);
        abort_unless($user->can('update', $achievement), 403);

        return view($request->boolean('modal') ? 'pages.achievements._edit-modal' : 'pages.achievements.create', $this->formData($achievement, $request));
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement, AchievementService $service): RedirectResponse|JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->update($achievement, $request->validated(), $actor);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Prestasi berhasil diperbarui.',
                'redirect' => route('achievements.index'),
            ]);
        }

        return redirect()->route('achievements.show', $achievement)->with('success', 'Prestasi berhasil diperbarui.');
    }

    /** @return array<string, mixed> */
    private function formData(?Achievement $achievement, Request $request): array
    {
        return [
            'achievement' => $achievement,
            'isEdit' => $achievement !== null,
            'students' => Student::query()->availableForService()
                ->with(['classMemberships' => fn ($memberships) => $memberships
                    ->active()
                    ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                    ->with('classroom')])
                ->orderBy('name')->get(),
            'types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
            'preselectedStudentId' => $request->integer('student_id') ?: null,
        ];
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'types' => ReferenceValue::query()->active()->forCategory('achievement_type')->orderBy('sort_order')->get(),
            'levels' => ReferenceValue::query()->active()->forCategory('achievement_level')->orderBy('sort_order')->get(),
        ];
    }
}
