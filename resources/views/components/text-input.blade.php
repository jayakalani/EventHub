@props(['disabled' => false, 'titleCase' => false])

<input @disabled($disabled)
    @if ($titleCase) data-title-case @endif
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
