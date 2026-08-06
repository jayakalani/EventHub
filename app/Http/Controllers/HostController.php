<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\HostsSubscription;
use App\Models\ticketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use View;

class HostController extends Controller
{
    public function create()
    {
        return view('organizer/hosts/create-host-form');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:hosts'],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/hosts/', $fileName);
        }

        $host = Host::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'contact_number' => $validatedData['contact_number'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id, // nullable
            'is_active' => true, // default true

        ]);

        return redirect()->route('organizer.hosts')->with('success', 'New Host was added successfully.');
    }

    /*public function index()
    {
        $hosts = Host::all();
        //$hostSubscription = HostsSubscription::all();

        //return view('hosts/view-hosts', ['hosts' => $hosts, 'hostSubscription' => $hostSubscription]);
        return view('organizer/hosts/index', ['hosts' => $hosts, ]);
    }*/

    public function index(Request $request)
    {
        $query = Host::query();

        // Search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Status Filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Date Range Filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Latest First + Pagination
        $hosts = $query->latest()->paginate(20)->withQueryString();

        return view('organizer.hosts.index', compact('hosts'));
    }

    /**
     * Display host details and events for organizers.
     */
    public function organizerShow(Host $host)
    {
        $host->loadCount(['hostLikes', 'hostFollows']);

        $events = $host->events()
            ->with('eventCategory')
            ->latest()
            ->get();

        return view('organizer.hosts.show', compact('host', 'events'));
    }

    /**
     * Display a read-only listing of hosts for attendees.
     */
    public function attendeeIndex(Request $request)
    {
        $query = Host::query()
            ->where('is_active', true)
            ->withCount(['events' => function ($eventQuery) {
                $eventQuery->visibleToAttendees();
            }, 'hostLikes', 'hostFollows'])
            ->withExists(['hostLikes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['hostFollows as is_followed' => function ($followQuery) {
                $followQuery->where('user_id', Auth::id());
            }]);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $hosts = $query->latest()->paginate(20)->withQueryString();

        return view('attendee.hosts.index', compact('hosts'));
    }

    /**
     * Display read-only host details and events for attendees.
     */
    public function attendeeShow(Host $host)
    {
        if (! $host->is_active) {
            abort(404);
        }

        $host->loadCount(['hostLikes', 'hostFollows']);
        $host->loadExists([
            'hostLikes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            },
            'hostFollows as is_followed' => function ($followQuery) {
                $followQuery->where('user_id', Auth::id());
            },
        ]);

        $events = $host->events()
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

        return view('attendee.hosts.show', compact('host', 'events'));
    }

    public function viewProfile(int $id)
    {
        $host = Host::findOrFail($id);
        $events = $host->events;
        $ticketCategories = ticketCategory::all();
        $hostSubscription = HostsSubscription::all();

        return view('hosts/view-host-profile', [
            'host' => $host,
            'events' => $events,
            'ticketCategories' => $ticketCategories,
            'hostSubscription' => $hostSubscription,
        ]);
    }

    public function toggleActive(int $id)
    {
        $host = Host::findOrFail($id);
        $host->is_active = $host->is_active ? 0 : 1;
        $host->save();

        return redirect()->back()->with('success', 'Host status updated successfully.');
    }

    public function edit(int $id)
    {
        $host = Host::findOrFail($id);

        // dd($author);
        return view('organizer/hosts/edit', ['host' => $host]);
    }

    public function update(int $id, Request $request)
    {
        $host = Host::findOrFail($id);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Host::class)->ignore($host->id)],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',

        ]);

        $default_host_cover = $host->cover;

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/hosts/', $fileName);
            $host->cover = $fileName;
        } else {
            // Set default cover image if no file is uploaded
            $host->cover = $host->cover ?? 'images/default-cover.jpg';
        }

        $host->name = $validatedData['name'];
        $host->email = $validatedData['email'];
        $host->contact_number = $validatedData['contact_number'];

        $host->save();

        return redirect()->route('organizer.hosts')
            ->with('success', 'Host updated successfully.');

    }

    public function destroy(Request $request, $id)
    {
        $host = Host::findOrFail($id);

        $host->delete();

        return redirect()->route('organizer.hosts')->with('success', "Host {$host->name} has been deleted.");

    }

    /**
     * Export hosts as CSV
     */
    public function exportCsv(Request $request)
    {
        $hosts = Host::all();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Email', 'Contact Number', 'Status', 'Created At'];

        foreach ($hosts as $host) {
            $csvData[] = [
                $host->id,
                $host->name,
                $host->email,
                $host->contact_number,
                $host->is_active ? 'Active' : 'Inactive',
                $host->created_at->format('Y-m-d H:i'),
            ];
        }

        $filename = 'hosts_'.now()->format('Ymd_His').'.csv';
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

    /**
     * Export hosts as PDF
     */
    public function exportPdf(Request $request)
    {
        $hosts = Host::all();

        $pdf = \PDF::loadView('organizer.exports.hosts_pdf', compact('hosts'));

        return $pdf->download('hosts_'.now()->format('Ymd_His').'.pdf');
    }
}
