<?php

namespace App\Http\Controllers;

use App\Enums\AdminNotificationCategory;
use App\Enums\AttendeeNotificationCategory;
use App\Enums\CroNotificationCategory;
use App\Enums\OrganizerNotificationCategory;
use App\Models\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $user->loadMissing('userRole');

        $roleName = $user->userRole?->name_en;
        $isAdmin = $roleName === UserRole::ADMIN;
        $isCro = $roleName === UserRole::CRO;
        $isOrganizer = $roleName === UserRole::ORGANIZER;
        $categoryClass = match (true) {
            $isAdmin => AdminNotificationCategory::class,
            $isCro => CroNotificationCategory::class,
            $isOrganizer => OrganizerNotificationCategory::class,
            default => AttendeeNotificationCategory::class,
        };

        $activeCategory = $categoryClass::tryFrom($request->string('category')->toString());

        $status = $this->pickOption($request->string('status')->toString(), ['unread', 'read'], 'all');
        $range = $this->pickOption($request->string('range')->toString(), ['today', 'week', 'month'], 'all');
        $sort = $this->pickOption($request->string('sort')->toString(), ['oldest'], 'newest');
        $search = trim($request->string('q')->toString());

        $typeOptions = $activeCategory
            ? $activeCategory->typeLabels()
            : $categoryClass::allTypeLabels();

        $type = $request->string('type')->toString();
        $type = array_key_exists($type, $typeOptions) ? $type : null;

        $notificationsQuery = $user->notifications();

        if ($activeCategory) {
            $notificationsQuery->whereIn('data->type', $activeCategory->types());
        }

        if ($type) {
            $notificationsQuery->where('data->type', $type);
        }

        if ($status === 'unread') {
            $notificationsQuery->whereNull('read_at');
        } elseif ($status === 'read') {
            $notificationsQuery->whereNotNull('read_at');
        }

        $since = match ($range) {
            'today' => now()->startOfDay(),
            'week' => now()->subDays(7),
            'month' => now()->subDays(30),
            default => null,
        };

        if ($since) {
            $notificationsQuery->where('created_at', '>=', $since);
        }

        if ($search !== '') {
            $notificationsQuery->where('data->message', 'like', '%'.$search.'%');
        }

        $notifications = $notificationsQuery
            ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(15)
            ->withQueryString();

        $categoryCounts = collect($categoryClass::cases())
            ->mapWithKeys(fn ($category) => [
                $category->value => $user->unreadNotifications()
                    ->whereIn('data->type', $category->types())
                    ->count(),
            ]);

        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();

        $filters = [
            'status' => $status,
            'range' => $range,
            'sort' => $sort,
            'type' => $type,
            'q' => $search,
        ];

        return view('notifications.index', [
            'notifications' => $notifications,
            'activeCategory' => $activeCategory,
            'categories' => $categoryClass::cases(),
            'categoryClass' => $categoryClass,
            'categoryCounts' => $categoryCounts,
            'typeOptions' => $typeOptions,
            'filters' => $filters,
            'hasActiveFilters' => $status !== 'all' || $range !== 'all' || $sort !== 'newest' || $type !== null || $search !== '',
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'readCount' => max($totalCount - $unreadCount, 0),
            'isAdmin' => $isAdmin,
            'isCro' => $isCro,
            'isOrganizer' => $isOrganizer,
        ]);
    }

    /**
     * @param  list<string>  $allowed
     */
    private function pickOption(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public function markAsRead(string $id): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect($this->safeNotificationUrl($notification->data['url'] ?? null));
    }

    public function markAsUnread(string $id): RedirectResponse
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read_at' => null]);

        return back()->with('success', 'Notification marked as unread.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    private function safeNotificationUrl(mixed $url): string
    {
        $fallback = route('notifications.index');

        if (! is_string($url) || $url === '') {
            return $fallback;
        }

        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $appHost = parse_url($appUrl, PHP_URL_HOST);
        $parsed = parse_url($url);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        if (is_string($appHost) && $appHost !== ''
            && strcasecmp((string) ($parsed['host'] ?? ''), $appHost) === 0) {
            return $url;
        }

        return $fallback;
    }
}
