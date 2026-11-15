@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-[#E5EAF0] shadow-xs p-6 card-clean transition-all duration-200 ' . $class]) }}>
    {{ $slot }}
</div>
