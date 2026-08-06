<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    Ticket Check-in
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    Scan a guest QR code or enter a ticket number to mark attendance.
                </p>
            </div>

            <a href="{{ route('organizer.bookings.index', array_filter(['event_id' => $eventId])) }}"
                class="inline-flex items-center rounded-xl bg-slate-100 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                Back to Guest List
            </a>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <form method="POST" action="{{ route('organizer.bookings.scan.submit') }}" id="scan-form"
                        class="space-y-3">
                        @csrf

                        <div>
                            <label for="event_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Limit to event (optional)
                            </label>
                            <select name="event_id" id="event_id"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All my events</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" @selected((string) $eventId === (string) $event->id)>
                                        {{ $event->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="code" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Ticket number or QR payload
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" required autofocus
                                placeholder="TKT-XXXXXXXXXXXX or scanned QR text"
                                class="w-full rounded-xl border-slate-300 font-mono text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('code')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                            class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            Check In Guest
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" x-data="ticketScanner()">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Camera scan</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Use the device camera to read ticket QR codes. Falls back to manual entry if unsupported.
                            </p>
                        </div>
                        <button type="button" @click="toggle()"
                            class="shrink-0 rounded-xl bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                            x-text="active ? 'Stop Camera' : 'Start Camera'"></button>
                    </div>

                    <p class="mt-3 text-sm text-amber-700" x-show="error" x-text="error" x-cloak></p>

                    <div class="mt-4 overflow-hidden rounded-xl bg-slate-900" x-show="active" x-cloak>
                        <video x-ref="video" class="aspect-video w-full object-cover" playsinline muted></video>
                    </div>

                    <p class="mt-3 text-xs text-slate-500" x-show="active" x-cloak>
                        Point the camera at the ticket QR. A successful scan submits check-in automatically.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function ticketScanner() {
                return {
                    active: false,
                    error: '',
                    stream: null,
                    detector: null,
                    raf: null,
                    lastCode: '',
                    lastAt: 0,

                    async toggle() {
                        if (this.active) {
                            this.stop();
                            return;
                        }
                        await this.start();
                    },

                    async start() {
                        this.error = '';

                        if (!window.isSecureContext) {
                            this.error = 'Camera scanning requires HTTPS (or localhost). Use manual entry instead.';
                            return;
                        }

                        if (!('BarcodeDetector' in window)) {
                            this.error = 'QR camera scanning is not supported in this browser. Use manual ticket entry.';
                            return;
                        }

                        try {
                            this.detector = new BarcodeDetector({ formats: ['qr_code'] });
                            this.stream = await navigator.mediaDevices.getUserMedia({
                                video: { facingMode: { ideal: 'environment' } },
                                audio: false,
                            });
                            this.$refs.video.srcObject = this.stream;
                            await this.$refs.video.play();
                            this.active = true;
                            this.scanLoop();
                        } catch (e) {
                            this.error = 'Unable to access the camera. Check permissions or use manual entry.';
                            this.stop();
                        }
                    },

                    stop() {
                        this.active = false;
                        if (this.raf) {
                            cancelAnimationFrame(this.raf);
                            this.raf = null;
                        }
                        if (this.stream) {
                            this.stream.getTracks().forEach(track => track.stop());
                            this.stream = null;
                        }
                        if (this.$refs.video) {
                            this.$refs.video.srcObject = null;
                        }
                    },

                    async scanLoop() {
                        if (!this.active || !this.detector) return;

                        try {
                            const codes = await this.detector.detect(this.$refs.video);
                            if (codes.length) {
                                const value = (codes[0].rawValue || '').trim();
                                const now = Date.now();
                                if (value && (value !== this.lastCode || now - this.lastAt > 2500)) {
                                    this.lastCode = value;
                                    this.lastAt = now;
                                    document.getElementById('code').value = value;
                                    this.stop();
                                    document.getElementById('scan-form').submit();
                                    return;
                                }
                            }
                        } catch (e) {
                            // Keep looping; transient detect failures are normal.
                        }

                        this.raf = requestAnimationFrame(() => this.scanLoop());
                    },

                    destroy() {
                        this.stop();
                    }
                }
            }
        </script>
    @endpush
</x-app-layout>
