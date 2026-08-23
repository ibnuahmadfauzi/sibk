<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can('viewAny', UserNotification::class), 403);
        $query = $user->notifications()->latest();
        $filter = $request->string('filter')->toString();
        $query->when($filter === 'unread', fn ($notifications) => $notifications->unread());
        $query->when($request->string('category')->toString(), fn ($notifications, string $category) => $notifications->where('category', $category));

        return view('pages.notifications.index', [
            'notifications' => $query->paginate(20)->withQueryString(),
            'unreadCount' => $user->notifications()->unread()->count(),
            'activeFilter' => $filter === 'unread' ? 'unread' : 'all',
        ]);
    }

    public function open(Request $request, UserNotification $notification): RedirectResponse
    {
        abort_unless($request->user()?->can('update', $notification), 403);
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return redirect()->to($notification->actionUrl());
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->notifications()->unread()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
