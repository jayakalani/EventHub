@php
    $templates = $templates ?? [];
    $textareaId = $textareaId ?? 'reply-message';
@endphp

@if (count($templates))
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Templates</span>
        @foreach ($templates as $template)
            <button type="button"
                class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                onclick="(function(){const el=document.getElementById({{ \Illuminate\Support\Js::from($textareaId) }});if(!el)return;el.value={{ \Illuminate\Support\Js::from($template['body']) }};el.dispatchEvent(new Event('input'));el.focus();})()">
                {{ $template['label'] }}
            </button>
        @endforeach
    </div>
@endif
