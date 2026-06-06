<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Event') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <form method="POST" action="{{ route('organizer.events.update', $event->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="border border-dark border-2 rounded-2 p-4">
                <!-- Event Name -->
                <div class="row mb-3">
                    <label for="name" class="col-md-4 col-form-label text-md-end">Event Name:</label>
                    <div class="col-md-6">
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                            name="name" value="{{ old('name', $event->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Hosted By -->
                <div class="row mb-3">
                    <label for="hosted_by" class="col-md-4 col-form-label text-md-end">Hosted By:</label>
                    <div class="col-md-6">
                        <select class="form-control" id="hosted_by" name="hosted_by" required>
                            <option value="">Select Host Person</option>
                            @foreach ($hosts as $host)
                                <option value="{{ $host->id }}"
                                    {{ $event->hosted_by == $host->id ? 'selected' : '' }}>
                                    {{ $host->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('hosted_by')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Category -->
                <div class="row mb-3">
                    <label for="category_id" class="col-md-4 col-form-label text-md-end">Category:</label>
                    <div class="col-md-6">
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach ($event_categories as $event_category)
                                <option value="{{ $event_category->id }}"
                                    {{ $event->category_id == $event_category->id ? 'selected' : '' }}>
                                    {{ $event_category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Date & Time -->
                <div class="row mb-3">
                    <label for="date" class="col-md-4 col-form-label text-md-end">Date & Time:</label>
                    <div class="col-md-3">
                        <input id="date" type="date" class="form-control @error('date') is-invalid @enderror"
                            name="date" value="{{ old('date', $event->date) }}" required>
                        @error('date')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <input id="time" type="time" class="form-control @error('time') is-invalid @enderror"
                            name="time" value="{{ old('time', $event->time) }}" required>
                        @error('time')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Place -->
                <div class="row mb-3">
                    <label for="place" class="col-md-4 col-form-label text-md-end">Place:</label>
                    <div class="col-md-6">
                        <input id="place" type="text" class="form-control @error('place') is-invalid @enderror"
                            name="place" value="{{ old('place', $event->place) }}" required>
                        @error('place')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Seats -->
                <div class="row mb-3">
                    <label for="no_of_seats" class="col-md-4 col-form-label text-md-end">Number Of Seats:</label>
                    <div class="col-md-6">
                        <input id="no_of_seats" type="text"
                            class="form-control @error('no_of_seats') is-invalid @enderror" name="no_of_seats"
                            value="{{ old('no_of_seats', $event->no_of_seats) }}" required>
                        @error('no_of_seats')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div class="row mb-3">
                    <label for="description" class="col-md-4 col-form-label text-md-end">Event Description:</label>
                    <div class="col-md-6">
                        <textarea name="description" id="description" required style="width:100%;height:150px;">{{ old('description', $event->description) }}</textarea>
                        @error('description')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- CRO -->
                <div class="row mb-3">
                    <label for="contact_person" class="col-md-4 col-form-label text-md-end">Customer Relations
                        Officer:</label>
                    <div class="col-md-6">
                        <select class="form-control" id="contact_person" name="contact_person" required>
                            <option value="">Select Contact Person</option>
                            @foreach ($croUsers as $user)
                                <option value="{{ $user->id }}"
                                    {{ $event->contact_person == $user->id ? 'selected' : '' }}>
                                    {{ $user->first_name }} {{ $user->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('contact_person')
                            <span class="invalid-feedback"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>

                <!-- Cover -->
                <div class="row mb-3">
                    <label for="cover" class="col-md-4 col-form-label text-md-end">Cover:</label>
                    <div class="col-md-6">
                        <input type="file" id="cover" name="cover" class="form-control-file"
                            accept=".jpg,.jpeg,.png">
                        <small class="text-muted">Accepted file type: JPG, JPEG, PNG | must be less than 2MB</small>
                        @if ($event->cover)
                            <div class="mt-2">
                                <img src="{{ asset('storage/' . $event->cover) }}" alt="Current Cover"
                                    class="w-32 h-20 object-cover rounded">
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 mt-4">
                Update Event
            </button>
        </form>
    </div>
</x-app-layout>
