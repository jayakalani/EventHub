<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Rating::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $ratings = $this->ratingsQuery($organizerId, $filters)
            ->with(['user', 'event'])
            ->latest('ratings.created_at')
            ->paginate(20)
            ->withQueryString();

        $statsQuery = $this->ratingsQuery($organizerId, $filters);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'average' => round((float) ((clone $statsQuery)->avg('score') ?? 0), 1),
            'events' => (int) (clone $statsQuery)->distinct()->count('event_id'),
            'five_star' => (clone $statsQuery)->where('score', 5)->count(),
        ];

        $events = Event::query()
            ->forFilter()
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name', 'deleted_at']);

        return view('organizer.reviews.index', compact('ratings', 'stats', 'events', 'filters'));
    }

    /**
     * @return array{search?: string|null, event_id?: int|null, score?: int|null, from_date?: string|null, to_date?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'search' => $request->filled('search') ? $request->input('search') : null,
            'event_id' => $request->filled('event_id') ? $request->input('event_id') : null,
            'score' => $request->filled('score') ? $request->input('score') : null,
            'from_date' => $request->filled('from_date') ? $request->input('from_date') : null,
            'to_date' => $request->filled('to_date') ? $request->input('to_date') : null,
        ]);

        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', Auth::id())),
            ],
            'score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }

    /**
     * @param  array{search?: string|null, event_id?: int|null, score?: int|null, from_date?: string|null, to_date?: string|null}  $filters
     */
    private function ratingsQuery(int $organizerId, array $filters): Builder
    {
        $query = Rating::query()
            ->whereHas('event', fn (Builder $q) => $q->withTrashed()->createdByOrganizer($organizerId));

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['score'])) {
            $query->where('score', $filters['score']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('ratings.created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('ratings.created_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('user', function (Builder $userQuery) use ($search) {
                    $userQuery->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('event', function (Builder $eventQuery) use ($search) {
                    $eventQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query;
    }
}
