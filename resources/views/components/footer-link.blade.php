@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
    {{ $attributes->class([
        'inline-flex text-sm transition-colors duration-200',
        'font-medium text-white' => $active,
        'text-slate-400 hover:text-white' => ! $active,
    ]) }}>
    {{ $slot }}
</a>
