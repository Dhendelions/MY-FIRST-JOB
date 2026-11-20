@extends('layouts.app', ['title' => 'Profil Pengguna — ' . $profileData['name']])

@section('content')
<div class="p-4 sm:p-6 lg:p-8 max-w-2xl mx-auto w-full">
    <div class="bg-white rounded-2xl border border-[#E5EAF0] shadow-xs p-6 sm:p-8 relative overflow-hidden card-clean">
        
        <!-- Header Accent -->
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-[#142B4A]"></div>

        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-5 pt-2">
            <img class="w-24 h-24 rounded-2xl object-cover ring-4 ring-[#E5EAF0] shadow-xs shrink-0" 
                 src="{{ $profileData['avatar_url'] }}" 
                 alt="{{ $profileData['name'] }}">
            
            <div class="text-center sm:text-left flex-1 min-w-0">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                    <h1 class="text-xl sm:text-2xl font-black text-[#263445] tracking-tight">{{ $profileData['name'] }}</h1>
                    @if($profileData['is_self'])
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-[#F5F7FA] text-[#142B4A] border border-[#E5EAF0]">Profil Anda</span>
                    @endif
                </div>

                <p class="text-xs sm:text-sm text-[#526780] font-bold font-mono mt-1 capitalize">{{ $profileData['role_title'] }}</p>

                <div class="mt-4 flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-[#7B8794]">
                    <div class="flex items-center gap-1.5 bg-[#F5F7FA] px-3 py-1.5 rounded-xl border border-[#E5EAF0]">
                        <svg class="w-4 h-4 text-[#7B8794]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Bergabung: <strong class="text-[#263445]">{{ $profileData['joined_since'] }}</strong></span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-[#F5F7FA] px-3 py-1.5 rounded-xl border border-[#E5EAF0]">
                        <svg class="w-4 h-4 text-[#7B8794]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span>Ruang Kerja: <strong class="text-[#263445]">{{ $profileData['total_workspaces'] }}</strong></span>
                    </div>
                </div>

                <!-- Friendship Action -->
                @if(!$profileData['is_self'])
                    <div class="mt-6 pt-5 border-t border-[#E5EAF0] flex items-center justify-center sm:justify-start gap-3">
                        @if($profileData['friendship_status'] === 'friends')
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#DCFCE7] text-[#15803d] text-xs font-bold border border-emerald-200">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Rekan Tim Terhubung
                            </span>
                        @elseif($profileData['friendship_status'] === 'pending_sent')
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#FEF3C7] text-[#b45309] text-xs font-bold border border-amber-200">
                                <svg class="w-4 h-4 text-amber-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Permintaan Dikirim (Menunggu)
                            </span>
                        @elseif($profileData['friendship_status'] === 'pending_received')
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-sky-50 text-sky-700 text-xs font-bold border border-sky-200">
                                Permintaan Masuk untuk Anda
                            </span>
                        @else
                            <form action="{{ route('friends.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="friend_id" value="{{ $profileData['id'] }}">
                                <button type="submit" class="px-4 py-2 rounded-xl bg-[#142B4A] hover:bg-[#1B355A] text-white text-xs font-bold shadow-xs flex items-center gap-1.5 transition-all duration-200 hover:-translate-y-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    Tambah Rekan Tim
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-8 pt-4 border-t border-[#E5EAF0] flex justify-between items-center text-xs text-[#7B8794]">
            <a href="{{ route('dashboard') }}" class="hover:text-[#142B4A] font-bold flex items-center gap-1 transition-colors">
                ← Kembali ke Dasbor
            </a>
            <span class="font-medium">Dadhen Mandiri</span>
        </div>
    </div>
</div>
@endsection
