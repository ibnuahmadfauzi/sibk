<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        Gate::authorize('viewAuditHistory');
        $query = AuditLog::query()->with('actor');
        if (! $user->hasRole('koordinator_bk')) {
            $query->where(function (Builder $access) use ($user): void {
                if ($user->hasAnyRole(['guru_bk', 'waka_kesiswaan'])) {
                    $access->orWhere('actor_id', $user->getKey());
                }
                if ($user->hasRole('admin_it')) {
                    $access->orWhere(function (Builder $technical): void {
                        foreach (['auth.', 'account.', 'dapodik.', 'etatib.', 'identity.', 'correction.master_'] as $prefix) {
                            $technical->orWhere('action', 'like', $prefix.'%');
                        }
                    });
                }
            });
        }

        $search = $request->string('search')->trim()->toString();
        $query->when($search, fn ($logs) => $logs->where(function ($filter) use ($search): void {
            $filter->where('summary', 'like', '%'.$search.'%')
                ->orWhere('action', 'like', '%'.$search.'%')
                ->orWhereHas('actor', fn ($actors) => $actors->where('name', 'like', '%'.$search.'%'));
        }));
        $query->when($request->string('action_type')->toString(), fn ($logs, string $action) => $logs->where('action', 'like', $action.'%'));
        $period = $request->string('period')->toString();
        $query->when(in_array($period, ['today', 'week', 'month'], true), fn ($logs) => $logs->where('created_at', '>=', match ($period) {
            'today' => today(),
            'week' => now()->subDays(7),
            default => now()->subDays(30),
        }));

        return view('pages.history.index', [
            'historyLogs' => $query->latest()->paginate(30)->withQueryString(),
        ]);
    }
}
