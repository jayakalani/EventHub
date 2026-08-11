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
        return view('organizer.artists.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:artists'],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $fileName = null;
        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/artists/', $fileName);
        }

        Artist::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'contact_number' => $validatedData['contact_number'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id,
            'is_active' => true,
        ]);

        return redirect()->route('organizer.artists')->with('success', 'New Artist was added successfully.');
    }

    public function index(Request $request)
    {
        $query = Artist::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $artists = $query->latest()->paginate(20)->withQueryString();

        return view('organizer.artists.index', compact('artists'));
    }

    public function organizerShow(Artist $artist)
    {
        $artist->loadCount(['artistLikes', 'artistFollows']);

        $events = $artist->events()
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

    public function toggleActive(int $id)
    {
        $artist = Artist::findOrFail($id);
        $artist->is_active = $artist->is_active ? 0 : 1;
        $artist->save();

        return redirect()->back()->with('success', 'Artist status updated successfully.');
    }

    public function edit(int $id)
    {
        $artist = Artist::findOrFail($id);

        return view('organizer.artists.edit', ['artist' => $artist]);
    }

    public function update(int $id, Request $request)
    {
        $artist = Artist::findOrFail($id);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Artist::class)->ignore($artist->id)],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/artists/', $fileName);
            $artist->cover = $fileName;
        } else {
            $artist->cover = $artist->cover ?? 'images/default-cover.jpg';
        }

        $artist->name = $validatedData['name'];
        $artist->email = $validatedData['email'];
        $artist->contact_number = $validatedData['contact_number'];
        $artist->save();

        return redirect()->route('organizer.artists')
            ->with('success', 'Artist updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $artist = Artist::findOrFail($id);
        $artist->delete();

        return redirect()->route('organizer.artists')->with('success', "Artist {$artist->name} has been deleted.");
    }

    public function exportCsv(Request $request)
    {
        $artists = Artist::all();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Email', 'Contact Number', 'Status', 'Created At'];

        foreach ($artists as $artist) {
            $csvData[] = [
                $artist->id,
                $artist->name,
                $artist->email,
                $artist->contact_number,
                $artist->is_active ? 'Active' : 'Inactive',
                $artist->created_at->format('Y-m-d H:i'),
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
        $artists = Artist::all();

        $pdf = \PDF::loadView('organizer.exports.artists_pdf', compact('artists'));

        return $pdf->download('artists_'.now()->format('Ymd_His').'.pdf');
    }
}
