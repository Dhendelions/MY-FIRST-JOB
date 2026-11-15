@props(['name', 'title' => '', 'maxWidth' => 'md'])

@php
$maxWidthClasses = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
][$maxWidth] ?? 'max-w-md';
@endphp

<div x-show="activeModal === '{{ $name }}'" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-50 overflow-y-auto" 
     x-cloak>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
        <div class="fixed inset-0 bg-[#142B4A]/30 backdrop-blur-xs transition-opacity" @click="activeModal = null"></div>

        <div class="relative bg-white rounded-2xl {{ $maxWidthClasses }} w-full p-6 text-left shadow-xl border border-[#E5EAF0] my-8 modal-enter">
            <div class="flex items-center justify-between pb-3 border-b border-[#E5EAF0]">
                <h3 class="text-base font-bold text-[#263445]">{{ $title }}</h3>
                <button @click="activeModal = null" class="text-[#7B8794] hover:text-[#263445] p-1 rounded-lg hover:bg-[#F5F7FA] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="mt-4">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
