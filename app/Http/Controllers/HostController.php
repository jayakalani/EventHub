<?php

namespace App\Http\Controllers;

use App\Models\Host;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HostController extends Controller
{
    public function create()
    {
        $this->authorize('create', Host::class);

        return view('organizer.hosts.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Host::class);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:hosts'],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $fileName = null;
        if ($request->hasFile('cover')) {
            $fileName = $this->storeHostCover($request->file('cover'));
        }

        Host::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'contact_number' => $validatedData['contact_number'],
            'cover' => $fileName,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        return redirect()->route('organizer.hosts')->with('success', 'New Host was added successfully.');
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Host::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $query = Host::query()->createdByOrganizer((int) Auth::id());

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

        $hosts = $query->withCount('events')->latest()->paginate(20)->withQueryString();

        return view('organizer.hosts.index', compact('hosts'));
    }

    public function organizerShow(Host $host)
    {
        $this->authorize('view', $host);

        $events = $host->events()
            ->createdByOrganizer((int) Auth::id())
            ->with('eventCategory')
            ->latest()
            ->get();

        return view('organizer.hosts.show', compact('host', 'events'));
    }

    public function toggleActive(Host $host)
    {
        $this->authorize('toggleActive', $host);

        if ($host->is_active && $host->hasLinkedEvents()) {
            return redirect()->back()->with(
                'error',
                'This host cannot be deactivated because they are linked to one or more events.'
            );
        }

        $host->is_active = $host->is_active ? 0 : 1;
        $host->save();

        return redirect()->back()->with('success', 'Host status updated successfully.');
    }

    public function edit(Host $host)
    {
        $this->authorize('update', $host);

        return view('organizer.hosts.edit', ['host' => $host]);
    }

    public function update(Host $host, Request $request)
    {
        $this->authorize('update', $host);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Host::class)->ignore($host->id)],
            'contact_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            $fileName = $this->storeHostCover($request->file('cover'));
            $this->deleteHostCoverFile($host->cover);
            $host->cover = $fileName;
        }

        $host->name = $validatedData['name'];
        $host->email = $validatedData['email'];
        $host->contact_number = $validatedData['contact_number'];
        $host->save();

        return redirect()->route('organizer.hosts')
            ->with('success', 'Host updated successfully.');
    }

    public function destroy(Host $host)
    {
        $this->authorize('delete', $host);

        if ($host->hasLinkedEvents()) {
            return redirect()->back()->with(
                'error',
                'This host cannot be deleted because they are linked to one or more events.'
            );
        }

        $name = $host->name;
        $this->deleteHostCoverFile($host->cover);
        $host->delete();

        return redirect()->route('organizer.hosts')->with('success', "Host {$name} has been deleted.");
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Host::class);

        $hosts = Host::query()->createdByOrganizer((int) Auth::id())->get();

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

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Host::class);

        $hosts = Host::query()->createdByOrganizer((int) Auth::id())->get();

        $pdf = \PDF::loadView('organizer.exports.hosts_pdf', compact('hosts'));

        return $pdf->download('hosts_'.now()->format('Ymd_His').'.pdf');
    }

    private function storeHostCover(\Illuminate\Http\UploadedFile $file): string
    {
        $fileName = uniqid('host_', true).'_'.time().'.'.$file->getClientOriginalExtension();
        $file->move(public_path('uploads/covers/hosts'), $fileName);

        return $fileName;
    }

    private function deleteHostCoverFile(?string $fileName): void
    {
        if (! $fileName) {
            return;
        }

        $path = public_path('uploads/covers/hosts/'.$fileName);

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
