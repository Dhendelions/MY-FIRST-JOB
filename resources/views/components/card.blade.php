@props(['class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 ' . $class]) }}>
    {{ $slot }}
</div>
