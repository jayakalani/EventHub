<!DOCTYPE html>
<html>

<head>
    <title>{{ $event->name }} Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        h2,
        h3 {
            margin-bottom: 10px;
        }

        p {
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    {{-- Event Details --}}
    <h2>{{ $event->name }}</h2>
    <p><strong>Hosted By:</strong> {{ $event->host->name ?? 'N/A' }}</p>
    <p><strong>Category:</strong> {{ $event->eventCategory->name ?? 'N/A' }}</p>
    <p><strong>Date:</strong> {{ $event->date }} {{ $event->time }}</p>
    <p><strong>Place:</strong> {{ $event->place }}</p>
    <p><strong>Total Seats:</strong> {{ $event->no_of_seats }}</p>
    <p><strong>Status:</strong> {{ ucfirst($event->status) }}</p>
    <p><strong>Contact Person:</strong> {{ $event->contactPerson->name ?? 'N/A' }}</p>
    <p><strong>Description:</strong> {{ $event->description }}</p>

    {{-- Seat Categories --}}
    <h3>Seat Categories</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Total Seats</th>
                <th>Available Seats</th>
                <th>Price</th>
                <th>Ticket Color</th>
                <th>Active</th>
                <th>Booking Start</th>
                <th>Booking End</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($seatCategories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->description ?? '-' }}</td>
                    <td>{{ $category->no_of_seats }}</td>
                    <td>{{ $category->no_of_available_seats }}</td>
                    <td>{{ number_format($category->seat_price, 2) }}</td>
                    <td>{{ $category->ticket_color }}</td>
                    <td>{{ $category->is_active ? 'Yes' : 'No' }}</td>
                    <td>{{ $category->booking_start ?? '-' }}</td>
                    <td>{{ $category->booking_end ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
