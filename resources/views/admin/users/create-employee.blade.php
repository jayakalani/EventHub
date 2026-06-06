<x-app-layout>
    <div class="max-w-2xl mx-auto p-6 bg-white shadow rounded">
        <h1 class="text-2xl font-bold mb-4">Create New Employee</h1>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0"></i>
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">Registration Failed</h3>
                        <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.employee.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-gray-700">First Name</label>
                <input type="text" name="first_name" class="w-full px-4 py-2 border rounded">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">Last Name</label>
                <input type="text" name="last_name" class="w-full px-4 py-2 border rounded">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">NIC</label>
                <input type="text" name="nic" class="w-full px-4 py-2 border rounded">
            </div>


            <div class="mb-4">
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" class="w-full px-4 py-2 border rounded">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">Contact Number</label>
                <input type="text" name="contact_number" class="w-full px-4 py-2 border rounded">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">User Role</label>
                <select name="role_id" class="w-full px-4 py-2 border rounded">
                    <option value="">-- Select Role --</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name_en }}</option>
                    @endforeach
                </select>
            </div>


            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Save Employee
            </button>
        </form>
    </div>
</x-app-layout>
