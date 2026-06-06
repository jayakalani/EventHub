<x-app-layout>
    <div class="max-w-3xl mx-auto p-8 bg-white shadow-lg rounded-2xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Create New Event</h1>

        {{-- Error messages --}}
        @if ($errors->any())
            <div class="mb-6 rounded-xl bg-red-50 border border-red-200 p-4">
                <h3 class="font-semibold text-red-700 mb-2">Event Creation Failed</h3>
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Success message --}}
        @if (session('status') === 'event-created')
            <div class="mb-6 rounded-xl bg-green-50 border border-green-200 p-4">
                <h3 class="font-semibold text-green-700 mb-2">Event Created</h3>
                <p class="text-sm text-green-600">Your event has been successfully created.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('organizer.events.store') }}" enctype="multipart/form-data"
            class="space-y-6">
            @csrf

            {{-- Event Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Event Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                @error('name')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Hosted By --}}
            <div>
                <label for="hosted_by" class="block text-sm font-medium text-gray-700">Hosted By</label>
                <select id="hosted_by" name="hosted_by"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                    <option value="">Select Host Person</option>
                    @foreach ($hosts as $host)
                        <option value="{{ $host->id }}">{{ $host->name }}</option>
                    @endforeach
                </select>
                @error('hosted_by')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category --}}
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                <select id="category_id" name="category_id"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                    <option value="">Select Category</option>
                    @foreach ($event_categories as $event_category)
                        <option value="{{ $event_category->id }}">{{ $event_category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Date & Time --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                    <input id="date" type="date" name="date" value="{{ old('date') }}"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                    @error('date')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="time" class="block text-sm font-medium text-gray-700">Time</label>
                    <input id="time" type="time" name="time" value="{{ old('time') }}"
                        class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required>
                    @error('time')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Place --}}
            <div>
                <label for="place" class="block text-sm font-medium text-gray-700">Place</label>
                <input id="place" type="text" name="place" value="{{ old('place') }}"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                @error('place')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Number of Seats --}}
            <div>
                <label for="no_of_seats" class="block text-sm font-medium text-gray-700">Number of Seats</label>
                <input id="no_of_seats" type="number" name="no_of_seats" value="{{ old('no_of_seats') }}"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                @error('no_of_seats')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Event Description</label>
                <textarea id="description" name="description" rows="4"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contact Person --}}
            <div>
                <label for="contact_person" class="block text-sm font-medium text-gray-700">Customer Relations
                    Officer</label>
                <select id="contact_person" name="contact_person"
                    class="mt-2 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required>
                    <option value="">Select Contact Person</option>
                    @foreach ($croUsers as $croUser)
                        <option value="{{ $croUser->id }}">{{ $croUser->first_name }} {{ $croUser->last_name }}
                        </option>
                    @endforeach
                </select>
                @error('contact_person')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Cover Image --}}
            <div>
                <label for="cover" class="block text-sm font-medium text-gray-700">Cover Image</label>
                <input id="cover" type="file" name="cover"
                    class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4
                              file:rounded-full file:border-0
                              file:text-sm file:font-semibold
                              file:bg-indigo-50 file:text-indigo-700
                              hover:file:bg-indigo-100"
                    accept=".jpg,.jpeg,.png" required>
                <p class="text-xs text-gray-500 mt-1">Accepted file types: JPG, JPEG, PNG | Max size: 2MB</p>
                @error('cover')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="pt-4">
                <button type="submit"
                    class="w-full inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-3 text-white font-semibold shadow hover:bg-indigo-700 transition">
                    Save Event
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
