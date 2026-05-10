<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $roleName = $user->userRole?->name_en;

        return match ($roleName) {
            UserRole::ADMIN => $this->admin(),
            UserRole::ORGANIZER => $this->organizer(),
            UserRole::CRO => $this->cro(),
            UserRole::ATTENDEE => $this->attendee(),
            default => redirect()->route('login')->with('error', 'Invalid role'),
        };
    }

    /**
     * Admin Dashboard
     */
    public function admin(): View
    {
        return view('admin.dashboard');
    }

    /**
     * Organizer Dashboard
     */
    public function organizer(): View
    {
        return view('organizer.dashboard');
    }

    /**
     * CRO Dashboard
     */
    public function cro(): View
    {
        return view('cro.dashboard');
    }

    /**
     * Attendee Dashboard
     */
    public function attendee(): View
    {
        return view('attendee.dashboard');
    }
}