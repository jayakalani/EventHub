<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ArtistController extends Controller
{
    public function create()
    {
        $this->authorize('create', Artist::class);

        return view('organizer.artists.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Artist::class);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:artists'],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $fileName = null;
        if ($request->hasfile('cover')) {
            $fileName = $this->storeArtistCover($request->file('cover'));
        }

        Artist::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'contact_number' => $validatedData['contact_number'],
            'cover' => $fileName,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        return redirect()->route('organizer.artists')->with('success', 'New Artist was added successfully.');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Artist::class);

        $artists = $this->filteredArtistsQuery($request)
            ->withCount(['events', 'artistFollows'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('organizer.artists.index', compact('artists'));
    }

    public function organizerShow(Artist $artist)
    {
        $this->authorize('view', $artist);

        $artist->loadCount(['artistLikes', 'artistFollows']);

        $events = $artist->events()
            ->createdByOrganizer((int) Auth::id())
            ->with('eventCategory')
            ->latest()
            ->get();

        return view('organizer.artists.show', compact('artist', 'events'));
    }

    public function attendeeIndex(Request $request)
    {
        $query = Artist::query()
            ->where('is_active', true)
            ->withCount(['events' => function ($eventQuery) {
                $eventQuery->visibleToAttendees();
            }, 'artistLikes', 'artistFollows'])
            ->withExists(['artistLikes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['artistFollows as is_followed' => function ($followQuery) {
                $followQuery->where('user_id', Auth::id());
            }]);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $artists = $query->latest()->paginate(20)->withQueryString();

        return view('attendee.artists.index', compact('artists'));
    }

    public function attendeeShow(Artist $artist)
    {
        if (! $artist->is_active) {
            abort(404);
        }

        $artist->loadCount(['artistLikes', 'artistFollows']);
        $artist->loadExists([
            'artistLikes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            },
            'artistFollows as is_followed' => function ($followQuery) {
                $followQuery->where('user_id', Auth::id());
            },
        ]);

        $events = $artist->events()
            ->visibleToAttendees()
            ->withCount('likes')
            ->withExists(['likes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['saves as is_saved' => function ($saveQuery) {
                $saveQuery->where('user_id', Auth::id());
            }])
            ->latest()
            ->get();

        return view('attendee.artists.show', compact('artist', 'events'));
    }

    public function toggleActive(Artist $artist)
    {
        $this->authorize('toggleActive', $artist);

        if ($artist->is_active) {
            if ($error = $this->removalBlockedMessage($artist, 'deactivated')) {
                return redirect()->back()->with('error', $error);
            }
        }

        $artist->is_active = $artist->is_active ? 0 : 1;
        $artist->save();

        return redirect()->back()->with('success', 'Artist status updated successfully.');
    }

    public function edit(Artist $artist)
    {
        $this->authorize('update', $artist);

        return view('organizer.artists.edit', ['artist' => $artist]);
    }

    public function update(Artist $artist, Request $request)
    {
        $this->authorize('update', $artist);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Artist::class)->ignore($artist->id)],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $fileName = $this->storeArtistCover($request->file('cover'));
            $this->deleteArtistCoverFile($artist->cover);
            $artist->cover = $fileName;
        }

        $artist->name = $validatedData['name'];
        $artist->email = $validatedData['email'];
        $artist->contact_number = $validatedData['contact_number'];
        $artist->save();

        return redirect()->route('organizer.artists')
            ->with('success', 'Artist updated successfully.');
    }

    public function destroy(Artist $artist)
    {
        $this->authorize('delete', $artist);

        if ($error = $this->removalBlockedMessage($artist, 'deleted')) {
            return redirect()->back()->with('error', $error);
        }

        $name = $artist->name;
        $this->deleteArtistCoverFile($artist->cover);
        $artist->delete();

        return redirect()->route('organizer.artists')->with('success', "Artist {$name} has been deleted.");
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Artist::class);

        $artists = $this->filteredArtistsQuery($request)->latest()->get();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Email', 'Contact Number', 'Status', 'Created At'];

        foreach ($artists as $artist) {
            $csvData[] = [
                $artist->id,
                $artist->name,
                $artist->email,
                $artist->contact_number,
                $artist->is_active ? 'Active' : 'Inactive',
                optional($artist->created_at)->format('Y-m-d H:i') ?? '',
            ];
        }

        $filename = 'artists_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Artist::class);

        $artists = $this->filteredArtistsQuery($request)->latest()->get();

        $pdf = \PDF::loadView('organizer.exports.artists_pdf', compact('artists'));

        return $pdf->download('artists_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Artist>
     */
    private function filteredArtistsQuery(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $query = Artist::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query;
    }

    private function removalBlockedMessage(Artist $artist, string $action): ?string
    {
        if ($artist->hasFollowers()) {
            return "This artist cannot be {$action} because attendees are following them.";
        }

        if ($artist->hasLinkedEvents()) {
            return "This artist cannot be {$action} because they are linked to one or more events.";
        }

        return null;
    }

    private function storeArtistCover(\Illuminate\Http\UploadedFile $file): string
    {
        $fileName = uniqid('artist_', true).'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('uploads/covers/artists'), $fileName);

        return $fileName;
    }

    private function deleteArtistCoverFile(?string $fileName): void
    {
        if (! $fileName || str_contains($fileName, '/') || str_contains($fileName, '\\')) {
            return;
        }

        $path = public_path('uploads/covers/artists/'.$fileName);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
