@php
    $claimRoute = $claimRoute ?? null;
    $reassignRoute = $reassignRoute ?? null;
    $croUsers = $croUsers ?? collect();
    $ticket = $ticket ?? null;
    $allowClaim = $allowClaim ?? true;
    $allowReassign = $allowReassign ?? true;
    $ownerLabel = $ownerLabel ?? ($ticket?->assignee?->full_name ?? 'Unassigned');
    $ownerHint = $ownerHint ?? null;
    $assignedViaEvent = $assignedViaEvent ?? false;
    $canReassign = $allowReassign
        && $reassignRoute
        && $ticket
        && ($ticket->isUnassigned() || $ticket->isAssignedTo(auth()->id()));
@endphp

<section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
        <h3 class="text-sm font-bold text-slate-900">Assignment</h3>
    </div>
    <div class="space-y-3 px-5 py-4 text-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current owner</p>
            <p class="mt-0.5 font-semibold text-slate-900">
                {{ $ownerLabel }}
            </p>
            @if ($ownerHint)
                <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $ownerHint }}</p>
            @endif
        </div>

        @if ($errors->has('assignment') || $errors->has('assigned_to'))
            <p class="text-xs text-rose-600">{{ $errors->first('assignment') ?: $errors->first('assigned_to') }}</p>
        @endif

        <div class="flex flex-wrap gap-2">
            @if ($allowClaim && $claimRoute && $ticket?->isUnassigned())
                <form action="{{ $claimRoute }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Claim
                    </button>
                </form>
            @elseif ($assignedViaEvent)
                <span class="inline-flex rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    Assigned via event
                </span>
            @elseif ($ticket?->isAssignedTo(auth()->id()))
                <span class="inline-flex rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                    Claimed by you
                </span>
            @elseif ($ticket && ! $ticket->isUnassigned())
                <span class="inline-flex rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                    Claimed by {{ $ticket->assignee?->full_name ?? 'another CRO' }}
                </span>
            @endif
        </div>

        @if ($reassignRoute && $canReassign)
            <form action="{{ $reassignRoute }}" method="POST" class="space-y-2 border-t border-slate-100 pt-3">
                @csrf
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ $ticket->isUnassigned() ? 'Assign to' : 'Reassign' }}
                </label>
                <select name="assigned_to" class="w-full rounded-xl border-slate-300 text-sm">
                    <option value="">Unassigned</option>
                    @foreach ($croUsers as $cro)
                        <option value="{{ $cro->id }}" @selected((int) $ticket?->assigned_to === (int) $cro->id)>
                            {{ $cro->first_name }} {{ $cro->last_name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="w-full rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">
                    Update assignment
                </button>
            </form>
        @elseif ($reassignRoute && $allowReassign && ! $canReassign)
            <p class="border-t border-slate-100 pt-3 text-xs text-slate-500">
                Only the assigned CRO can reassign this case.
            </p>
        @endif
    </div>
</section>
