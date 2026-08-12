<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\FiltersUsers;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    use FiltersUsers;

    /**
     * Users with staff roles (excludes attendees), honoring list filters when present.
     */
    private function employeesQuery(Request $request)
    {
        return $this->filteredUsersQuery($request)
            ->whereHas('userRole', function ($query) {
                $query->whereIn('name_en', UserRole::staffRoleNames());
            })
            ->latest();
    }

    public function create()
    {
        $roles = UserRole::activeStaffRoles()->get();

        return view('admin.users.create-employee', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'nic' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'nic')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'contact_number' => 'required|string|max:20',
            'role_id' => [
                'required',
                Rule::exists('user_roles', 'id')->where(function ($query) {
                    $query->whereIn('name_en', UserRole::staffRoleNames())
                        ->where('is_active', true)
                        ->whereNull('deleted_at');
                }),
            ],
        ]);

        // Admin-created staff are trusted and marked verified so they can
        // access role-gated routes that require the `verified` middleware.
        $employee = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'nic' => $validated['nic'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'],
            'role_id' => $validated['role_id'],
            'password' => Hash::make('Temp123'),
        ]);

        $employee->markEmailAsVerified();

        return redirect()->route('admin.users')
            ->with('success', 'Employee created successfully.');
    }

    public function exportCsv(Request $request)
    {
        $employees = $this->employeesQuery($request)->get();

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
        $employees = $this->employeesQuery($request)->get();

        $pdf = Pdf::loadView('admin.exports.employees_pdf', compact('employees'));

        return $pdf->download('employees_'.now()->format('Ymd_His').'.pdf');
    }
}
