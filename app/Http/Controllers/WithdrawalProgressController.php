<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DeleteWithdrawalProgressRequest;
use App\Http\Requests\StoreWithdrawalProgressFollowUpRequest;
use App\Http\Requests\StoreWithdrawalProgressRequest;
use App\Http\Requests\UpdateWithdrawalProgressRequest;
use App\Models\Student;
use App\Models\User;
use App\Models\WithdrawalProgress;
use App\Services\WithdrawalProgressService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class WithdrawalProgressController extends Controller
{
    public function create(Request $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can('create', WithdrawalProgress::class), 403);

        $students = $this->availableStudents($actor);

        return view('pages.withdrawals.create', [
            'withdrawalStudents' => $students,
            'withdrawalLookup' => $students->map(fn (Student $student): array => [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'classroom' => $student->classMemberships->first()?->classroom?->name ?? 'Rombel belum tercatat',
            ])->values(),
        ]);
    }

    public function store(StoreWithdrawalProgressRequest $request, WithdrawalProgressService $service): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $service->create($request->validated(), $actor);

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Penanganan pengunduran diri berhasil dicatat.');
    }

    public function edit(Request $request, WithdrawalProgress $withdrawal): View
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can('update', $withdrawal), 403);

        $withdrawal->load(['student', 'classroom', 'teacher']);

        return view(
            $request->boolean('modal') ? 'pages.withdrawals._edit-modal' : 'pages.withdrawals.edit',
            ['withdrawal' => $withdrawal],
        );
    }

    public function update(
        UpdateWithdrawalProgressRequest $request,
        WithdrawalProgress $withdrawal,
        WithdrawalProgressService $service,
    ): RedirectResponse|JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $withdrawal = $service->update($withdrawal, $request->validated(), $actor);

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Penanganan pengunduran diri berhasil diperbarui.');

            return response()->json([
                'message' => 'Penanganan pengunduran diri berhasil diperbarui.',
                'redirect' => route('cases.index', ['tab' => 'pengunduran-diri']),
                'data' => [
                    'recorded_on' => $withdrawal->recorded_on?->toDateString(),
                    'note' => $withdrawal->note,
                    'updated_at' => $withdrawal->updated_at?->toJSON(),
                ],
            ]);
        }

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Penanganan pengunduran diri berhasil diperbarui.');
    }

    public function destroy(
        DeleteWithdrawalProgressRequest $request,
        WithdrawalProgress $withdrawal,
        WithdrawalProgressService $service,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $service->destroy($withdrawal, $actor);

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Penanganan pengunduran diri beserta riwayat progres penanganannya berhasil dihapus.');
    }

    public function storeFollowUp(
        StoreWithdrawalProgressFollowUpRequest $request,
        WithdrawalProgress $withdrawal,
        WithdrawalProgressService $service,
    ): JsonResponse|RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $followUp = $service->addFollowUp($withdrawal, $request->validated(), $actor);
        $withdrawal->refresh();

        if ($request->wantsJson() || $request->ajax()) {
            $followUps = $withdrawal->followUps()->with('creator')->get();

            return response()->json([
                'message' => 'Progres penanganan berhasil ditambahkan.',
                'data' => [
                    'id' => $followUp->id,
                    'progress' => $followUp->progress,
                    'progress_label' => $followUp->progressLabel(),
                    'current_progress' => $withdrawal->progress,
                    'current_progress_label' => $withdrawal->progressLabel(),
                    'follow_up_date' => $followUp->follow_up_date->toDateString(),
                    'follow_up_date_formatted' => $followUp->follow_up_date->locale('id')->translatedFormat('d M Y'),
                    'notes' => $followUp->notes,
                    'follow_ups' => $followUps->map(fn ($item): array => [
                        'id' => $item->id,
                        'progress' => $item->progress,
                        'progress_label' => $item->progressLabel(),
                        'follow_up_date' => $item->follow_up_date->toDateString(),
                        'follow_up_date_formatted' => $item->follow_up_date->locale('id')->translatedFormat('d M Y'),
                        'notes' => $item->notes,
                        'creator_name' => $item->creator?->name,
                    ])->values(),
                ],
            ]);
        }

        return redirect()->route('cases.index', ['tab' => 'pengunduran-diri'])
            ->with('success', 'Progres penanganan pengunduran diri berhasil ditambahkan.');
    }

    /** @return Collection<int, Student> */
    private function availableStudents(User $actor): Collection
    {
        return Student::query()
            ->availableForService()
            ->forActiveTeacherAssignment($actor)
            ->whereNotIn('id', WithdrawalProgress::query()->select('student_id'))
            ->with(['classMemberships' => fn ($memberships) => $memberships
                ->active()
                ->whereHas('academicYear', fn ($years) => $years->where('is_active', true))
                ->with('classroom')])
            ->orderBy('name')
            ->get();
    }
}
