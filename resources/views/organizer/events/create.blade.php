<x-app-layout>
    <div class="max-w-2xl mx-auto p-6 bg-white shadow rounded">
        <h1 class="text-2xl font-bold mb-4">Create New Event</h1>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                <div class="flex gap-3">
                    <i class="bi bi-exclamation-circle text-red-600 dark:text-red-400 text-lg flex-shrink-0"></i>
                    <div>
                        <h3 class="font-semibold text-red-900 dark:text-red-300 mb-2">Event Creation Failed</h3>
                        <ul class="text-sm text-red-800 dark:text-red-200 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        @if (session('status') === 'event-created')
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200">
                <h3 class="font-semibold text-green-900 mb-2">Event Created</h3>
                <p class="text-sm text-green-800">Your event has been successfully created.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('organizer.events.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="border border-dark border-2 rounded-2">
                 <div class="row mb-3 mt-4 ">
                    <label for="name" class="col-md-4 col-form-label text-md-end">{{ __('Event Name: ') }}</label>

                    <div class="col-md-6">
                        <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3 mt-4 ">
                    <label for="hosted_by" class="col-md-4 col-form-label text-md-end">{{ __('Hosted By: ') }}</label>

                    <div class="col-md-6">
                        <div class="dropdown">
                            <select class="form-control" id="hosted_by" name="hosted_by" required>
                                <option value="">Select Host Person</option>
                                @foreach ($hosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @error('hosted_by')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                                
                <div class="row mb-3">
                    <label for="category_id" class="col-md-4 col-form-label text-md-end">{{ __('Category: ') }}</label>

                    <div class="col-md-6">
                        <div class="dropdown">
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                @foreach ($event_categories as $event_category)
                                <option value="{{ $event_category->id }}">{{ $event_category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @error('category_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>


                <div class="row mb-3">
                    <label for="date" class="col-md-4 col-form-label text-md-end">{{ __('Date & Time: ') }}</label>

                    <div class="col-md-3">
                        <input id="date" type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date') }}" required autocomplete="date" autofocus>

                        @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <input id="time" type="time" class="form-control @error('time') is-invalid @enderror" name="time" value="{{ old('time') }}" required autocomplete="time" autofocus>

                        @error('time')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>                             

                <div class="row mb-3">
                    <label for="place" class="col-md-4 col-form-label text-md-end">{{ __('Place: ') }}</label>

                    <div class="col-md-6">
                        <input id="place" type="text" class="form-control @error('place') is-invalid @enderror" name="place" value="{{ old('place') }}" required autocomplete="place" autofocus>

                        @error('place')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                                
                <div class="row mb-3">
                    <label for="no_of_seats" class="col-md-4 col-form-label text-md-end">{{ __('Number Of Seats: ') }}</label>

                    <div class="col-md-6">
                        <input id="no_of_seats" type="text" class="form-control @error('no_of_seats') is-invalid @enderror" name="no_of_seats" value="{{ old('no_of_seats') }}" required autocomplete="no_of_seats" autofocus>

                        @error('no_of_seats')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                                
                <div class="row mb-3">
                    <label for="description" class="col-md-4 col-form-label text-md-end">{{ __('Event Description: ') }}</label>

                    <div class="col-md-6">
                        <textarea name="description" id="description" placeholder="Enter your event description here" required autofocus style="width: 100%; height: 150px;" autofocus></textarea>

                        @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                                
                <div class="row mb-3">
                    <label for="contact_person" class="col-md-4 col-form-label text-md-end">{{ __('Customer Relations Officer: ') }}</label>

                    <div class="col-md-6">
                        <div class="dropdown">
                            <select class="form-control" id="contact_person" name="contact_person" required>
                                <option value="">Select Contact Person</option>
                                @foreach ($croUsers as $croUser)
                                <option value="{{ $croUser->id }}">{{ $croUser->first_name }} {{ $croUser->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        @error('contact_person')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <label for="cover" class="col-md-4 col-form-label text-md-end">{{ __('Cover: ') }} </label>
                    <div class="col md-6">
                                        
                        <input type="file" id ="cover" name= "cover" class="form-control-file col-md-4" accept=".jpg,.jpeg, .png" required>
                        <small class="text-muted">Accepted file type: JPG, JPEG, PNG | must be less than 2MB</small>
                    </div>
                </div>
            </div> 

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                Save Event
            </button>
        </form>
    </div>
</x-app-layout>
