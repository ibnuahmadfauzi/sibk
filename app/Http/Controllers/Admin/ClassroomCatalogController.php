<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ClassroomCatalog;
use App\Services\ClassroomCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ClassroomCatalogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manageDataMaster');

        $classrooms = ClassroomCatalog::query()->orderByDesc('is_active')->orderBy('name')->get();
        $groups = $classrooms->groupBy(fn (ClassroomCatalog $classroom): string => $this->jurusan($classroom->name))
            ->sortKeys(SORT_NATURAL);
        $jurusan = $request->query('jurusan');
        abort_if($jurusan !== null && (! is_string($jurusan) || ! $groups->has($jurusan)), 404);

        $activeYear = AcademicYear::query()->active()->first();
        $studentCounts = $activeYear === null ? collect() : Classroom::query()
            ->active()
            ->where('academic_year_id', $activeYear->getKey())
            ->withCount(['studentClassMemberships as student_count' => fn ($memberships) => $memberships
                ->active()
                ->where('academic_year_id', $activeYear->getKey())
                ->whereHas('student', fn ($students) => $students->active())])
            ->get()
            ->groupBy('classroom_catalog_id')
            ->map(fn ($classrooms) => $classrooms->sum('student_count'));

        return view('pages.data-master.classrooms', [
            'classrooms' => $classrooms,
            'groups' => $groups,
            'jurusan' => $jurusan,
            'activeYear' => $activeYear,
            'studentCounts' => $studentCounts,
        ]);
    }

    public function store(Request $request, ClassroomCatalogService $service): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $service->add($data['name'], $request->user());

        return redirect()->route('data-master.classrooms.index')->with('success', 'Rombel ditambahkan.');
    }

    public function update(Request $request, ClassroomCatalog $catalog, ClassroomCatalogService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'jurusan' => ['sometimes', 'string'],
        ]);
        $service->update($catalog, $data['name'], (bool) $data['is_active'], $request->user());

        $parameters = isset($data['jurusan']) ? ['jurusan' => $this->jurusan($data['name'])] : [];

        return redirect()->route('data-master.classrooms.index', $parameters)->with('success', 'Rombel diperbarui.');
    }

    private function jurusan(string $name): string
    {
        $name = (string) preg_replace('/\s+/u', ' ', trim($name));
        if (! preg_match('/^(?:XIII|XII|XI|X|1[0-3]) ([\p{L}].*)$/iu', $name, $matches)) {
            return 'Lainnya';
        }

        return mb_strtoupper(trim((string) preg_replace('/\s+\d+[A-Z]?$/iu', '', $matches[1])));
    }
}
