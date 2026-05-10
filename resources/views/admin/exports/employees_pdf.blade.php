<!DOCTYPE html>
<html>
<head>
    <title>Employees Export</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>Employees List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Contact</th><th>Role</th><th>Is Active</th><th>Is Locked</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td>{{ $employee->id }}</td>
                    <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    <td>{{ $employee->email }}</td>
                    <td>{{ $employee->contact_number }}</td>
                    <td>{{ $employee->userRole->name_en ?? '' }}</td>
                    <td>{{ $employee->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $employee->is_locked ? 'Locked' : 'Unlocked' }}</td>

                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>


