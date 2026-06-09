<!DOCTYPE html>
<html>

<head>
    <title>Events Export</title>
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
    <h2>Events</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Event Name</th>
                <th>Hosted By</th>
                <th>Category</th>
                <th>Date</th>
                <th>Time</th>
                <th>Place</th>
                <th>tickets</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->name }}</td>
                    <td>{{ $event->host->name ?? 'N/A' }}</td>
                    <td>{{ $event->eventCategory->name ?? 'N/A' }}</td>
                    <td>{{ $event->date }}</td>
                    <td>{{ $event->time }}</td>
                    <td>{{ $event->place }}</td>
                    <td>{{ $event->no_of_tickets }}</td>
                    <td>{{ ucfirst($event->status) }}</td>
                    <td>{{ $event->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
