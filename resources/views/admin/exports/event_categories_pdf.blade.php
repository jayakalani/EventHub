<!DOCTYPE html>
<html>

<head>
    <title>Event Categories Export</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <h2>Event Categories</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Cover</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->id }}</td>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->cover ?? 'N/A' }}</td>
                    <td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $category->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $category->creator ? trim($category->creator->first_name.' '.$category->creator->last_name) : 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
