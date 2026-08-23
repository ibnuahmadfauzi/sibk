<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyPreviewController extends Controller
{
    /** @var array<string, list<string>> */
    private const ALLOWED_QUERY = [
        'cases.index' => ['tab'],
        'students.legacy' => ['nisn', 'tab'],
        'assignments.cases.index' => ['case_no'],
        'corrections.create' => ['object_type', 'object_id', 'student_id', 'attribute'],
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        $destination = (string) $request->route('destination');
        abort_unless(array_key_exists($destination, app('router')->getRoutes()->getRoutesByName()), 404);
        $query = $request->only(self::ALLOWED_QUERY[$destination] ?? []);

        return redirect()->route($destination, array_filter($query, static fn (mixed $value): bool => filled($value)));
    }
}
