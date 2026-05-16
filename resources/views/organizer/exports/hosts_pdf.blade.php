<!DOCTYPE html>
<html>
<head>
    <title>Hosts Export</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h2>Hosts</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hosts as $host)
                <tr>
                    <td>{{ $host->id }}</td>
                    <td>{{ $host->name }}</td>
                    <td>{{ $host->email }}</td>
                    <td>{{ $host->contact_number }}</td>
                    <td>{{ $host->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $host->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
