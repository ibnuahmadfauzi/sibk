<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassroomCatalog;
use App\Services\ClassroomCatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ClassroomCatalogController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manageDataMaster');

        return view('pages.data-master.classrooms', [
            'classrooms' => ClassroomCatalog::query()->orderByDesc('is_active')->orderBy('name')->get(),
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
        ]);
        $service->update($catalog, $data['name'], (bool) $data['is_active'], $request->user());

        return redirect()->route('data-master.classrooms.index')->with('success', 'Rombel diperbarui.');
    }
}
