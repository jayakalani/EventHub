<!DOCTYPE html>
<html>
<head>
    <title>Assigned Events Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
    <h2>Assigned Events</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Organizer</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
                <th>Place</th>
                <th>Tickets</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->name }}</td>
                    <td>{{ $event->organizer?->full_name ?? '—' }}</td>
                    <td>{{ $event->eventCategory?->name ?? '—' }}</td>
                    <td>{{ $event->trashed() ? 'archived' : $event->status }}</td>
                    <td>{{ $event->formattedScheduleDate() ?: 'TBA' }}</td>
                    <td>{{ $event->displayPlace() }}</td>
                    <td>{{ (int) ($event->ticket_categories_sum_no_of_tickets ?: $event->no_of_tickets) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
