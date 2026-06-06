<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

class EmployeeController extends Controller
{
    public function create()
    {

        $roles = UserRole::whereIn('name_en', [
            'admin',
            'event organizer',
            'customer relations officer',
        ])->where('is_active', true)->get();

        return view('admin.users.create-employee', compact('roles'));
    }

    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nic' => 'required|string|max:20|unique:users,nic',
            'email' => 'required|email|unique:users,email',
            'contact_number' => 'required|string|max:20',
            'role_id' => 'required|exists:user_roles,id',
        ]);

        // Create employee (assuming you’re saving into users table)
        $employee = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'],
            'role_id' => $validated['role_id'],
            'password' => Hash::make('Temp123'), // ✅ temporary password
        ]);

        // Redirect back with success message
        return redirect()->route('admin.users')
            ->with('success', 'Employee created successfully.');
    }

    public function exportCsv(Request $request)
    {
        $employees = User::with('userRole')->get();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Email', 'Contact Number', 'Role', 'Is Locked', 'Is Active'];

        foreach ($employees as $employee) {
            $csvData[] = [
                $employee->id,
                $employee->first_name.' '.$employee->last_name,
                $employee->email,
                $employee->contact_number,
                $employee->userRole->name_en ?? '',
                $employee->is_locked ? 'Locked' : 'Unlocked',
                $employee->is_active ? 'Active' : 'Inactive',

            ];
        }

        $filename = 'employees_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $employees = User::with('userRole')->get();

        $pdf = Pdf::loadView('admin.exports.employees_pdf', compact('employees'));

        return $pdf->download('employees_'.now()->format('Ymd_His').'.pdf');
    }
}
