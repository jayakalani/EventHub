<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Host;
use App\Models\HostsSubscription;
use App\Models\Event;
use App\Models\SeatCategory;
use View;


class HostController extends Controller
{
    public function create()
    {
        return view('organizer/create-host-form');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:hosts'],
            'phone_number' => ['required', 'string', 'max:20'],
            'cover' => 'required|image|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $extension;
            $file->move('uploads/covers/hosts/', $fileName);
        }

        $host = Host::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id, //nullable
            'is_active' => true, //default true
            // is_active
            // is_default_password_changed
            // created_by
        ]);

        return redirect()->route('organizer.host.create')->with('success','New Host was added successfully.'
);
    }

    

    public function view()
    {
        $hosts = Host::all();
        $hostSubscription = HostsSubscription::all();

        return view('hosts/view-hosts', ['hosts' => $hosts, 'hostSubscription' => $hostSubscription]);
    }

    public function viewProfile(int $id)
    {
        $host = Host::findOrFail($id);
        $events = $host->events;
        $seatCategories = SeatCategory::all();
        $hostSubscription = HostsSubscription::all();

        return view('hosts/view-host-profile', [
            'host' => $host,
            'events' => $events,
            'seatCategories' => $seatCategories,
            'hostSubscription' => $hostSubscription
        ]);
    }

    public function edit(int $id)
    {
        $host = Host::findOrFail($id);

        // dd($author);
        return view('hosts/edit-host-profile', ['host' => $host]);
    }

    public function update(int $id, Request $request)
    {

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:hosts'],
            'phone_number' => ['required', 'string', 'max:20'],
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',

        ]);

        $host = Host::findOrFail($id);

        $default_host_cover = $host->cover;

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time() . '.' . $extension;
            $file->move('uploads/covers/hosts/', $fileName);
            $host->cover = $fileName;
        } else {
            // Set default cover image if no file is uploaded
            $host->cover = $host->cover ?? 'images/default-cover.jpg';
        }

        $host->name = $validatedData['name'];
        $host->email = $validatedData['email'];
        $host->phone_number = $validatedData['phone_number'];

        $host->save();

        return redirect()->route('organizer.host.create')
                 ->with('success', 'New Host was added successfully.');

    }
}
