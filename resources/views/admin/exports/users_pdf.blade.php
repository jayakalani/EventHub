<!DOCTYPE html>
<html>

<head>
    <title>Users Export</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>Users List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Role</th>
                <th>Is Active</th>
                <th>Is Locked</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->contact_number }}</td>
                    <td>{{ $user->userRole->name_en ?? '' }}</td>
                    <td>{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $user->is_locked ? 'Locked' : 'Unlocked' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
