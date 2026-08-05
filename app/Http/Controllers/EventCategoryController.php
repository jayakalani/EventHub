<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventCategorySubscription;
use App\Models\ticketCategory;
use App\Models\User;
use App\Services\AdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use View;

class EventCategoryController extends Controller
{
    public function createEventCategory()
    {
        return view('admin/event-categories/create-event-category-form');
    }

    public function storeEventCategory(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/event_categories/', $fileName);
        }

        $event = EventCategory::create([
            'name' => $validatedData['name'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id,
            'is_active' => true, // default true
        ]);

        return redirect()->route('admin.event-categories.index')->with('success', 'New Event Category was added successfully.');
    }

    /*public function index(EventCategory $eventCategory)
    {
        $event_categories = EventCategory::all();
        $event_category_subscription = EventCategorySubscription::all();

        return view('admin/event-categories/index', [
            'event_categories'=>$event_categories,
            'event_category_subscription'=>$event_category_subscription
        ]);
    }*/

    public function index(Request $request)
    {
        $query = EventCategory::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', 1);
                    break;
                case 'inactive':
                    $query->where('is_active', 0);
                    break;
            }
        }

        // Date range filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $event_categories = $query->paginate(10)->appends($request->all());

        return view('admin.event-categories.index', compact('event_categories'));

    }

    public function viewProfile(int $id)
    {
        $event_category = EventCategory::findOrFail($id);
        $events = $event_category->events;
        $ticketCategories = ticketCategory::all();
        $event_category_subscription = EventCategorySubscription::all();

        return view('event_categories/view-event-category-profile', [
            'event_category' => $event_category,
            'events' => $events,
            'ticketCategories' => $ticketCategories,
            'event_category_subscription' => $event_category_subscription,
        ]);
    }

    public function all()
    {
        $eventCategories = EventCategory::all();

        return view('event_categories/all', ['eventCategories' => $eventCategories]);
    }

    public function view(int $id)
    {
        $eventCategory = User::findOrFail($id);

        return view('event_category/view', ['eventCategory' => $eventCategory]);
    }

    public function edit(int $id)
    {
        $eventCategory = EventCategory::findOrFail($id);

        return view('admin.event-categories.edit', ['eventCategory' => $eventCategory]);
    }

    public function update(int $id, Request $request)
    {

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);
        $eventCategory = EventCategory::findorFail($id);
        $default_eventCategory_cover = $eventCategory->cover;

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/event_categories/', $fileName);
            $eventCategory->cover = $fileName;
        } else {
            // Set default cover image if no file is uploaded
            $eventCategory->cover = $eventCategory->cover ?? 'images/default-cover.jpg';
        }

        $eventCategory->name = $validatedData['name'];

        $eventCategory->save();

        return redirect()->route('admin.event-categories.index')->with('success', 'Event category updated successfully.');
    }

    public function toggleActive(int $id)
    {
        $eventCategory = EventCategory::findOrFail($id);
        $eventCategory->is_active = $eventCategory->is_active ? 0 : 1;
        $eventCategory->save();

        return redirect()->back()->with('success', 'Event category status updated successfully.');
    }

    public function exportCsv(Request $request)
    {
        $categories = EventCategory::all();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Cover', 'Is Active', 'Created At', 'Created By'];

        foreach ($categories as $category) {
            $csvData[] = [
                $category->id,
                $category->name,
                $category->cover ?? 'N/A',
                $category->is_active ? 'Active' : 'Inactive',
                $category->created_at->format('Y-m-d H:i'),
                $category->creator->first_name.' '.$category->creator->last_name ?? 'System',
            ];
        }

        $filename = 'event_categories_'.now()->format('Ymd_His').'.csv';
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
        $categories = EventCategory::all();

        $pdf = \PDF::loadView('admin.exports.event_categories_pdf', compact('categories'));

        return $pdf->download('event_categories_'.now()->format('Ymd_His').'.pdf');
    }

    public function destroy(Request $request, $id)
    {
        $EventCategory = EventCategory::findOrFail($id);
        $categoryName = $EventCategory->name;

        $EventCategory->delete();

        app(AdminNotificationService::class)->notifyCategoryDeleted($EventCategory, Auth::user());

        return redirect()->route('admin.event-categories.index')->with('success', "Event category {$categoryName} has been deleted.");

    }
}
