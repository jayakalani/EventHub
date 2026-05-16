<x-app-layout>
    <div class="max-w-3xl mx-auto py-10">
        <div class="bg-white shadow-lg rounded-xl p-8">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Create New Host</h2>

            @if (session('success'))
                <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <form method="POST" enctype="multipart/form-data" action="{{ route('organizer.host.store') }}" class="space-y-6">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="name" type="text" name="name"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input id="email" type="email" name="email"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           value="{{ old('email') }}" required>
                    @error('email')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Number -->
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <input id="contact_number" type="text" name="contact_number"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                           value="{{ old('contact_number') }}" required>
                    @error('contact_number')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cover Upload -->
                <div>
                    <label for="cover" class="block text-sm font-medium text-gray-700">Cover Image</label>
                    <input id="cover" type="file" name="cover"
                           class="mt-1 block w-full text-sm text-gray-700 border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                           accept=".jpg,.jpeg,.png" required>
                    <p class="text-xs text-gray-500 mt-1">Accepted: JPG, JPEG, PNG | Max 2MB</p>
                </div>

                <!-- Submit -->
                <div class="flex justify-center">
                    <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        Register Host
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
