@extends('layouts.dashboard')
@section('title', 'Tim Departemen')
@section('content')
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Tim departemen', 'description' => 'Kenali anggota tim dan pantau pembagian tugas untuk pelayanan yang seimbang.'])
<div class="dh-team-summary"><span><strong>{{ $total }}</strong> anggota tim</span><span><span class="dh-dot"></span><strong>{{ $active }}</strong> akun aktif</span><span><strong>{{ $total - $active }}</strong> akun nonaktif</span></div>
<section class="dh-panel">
<form class="dh-filters" method="GET" action="{{ route('administration.staff') }}"><div class="dh-search"><label for="staff-search">Cari anggota tim</label><div><i class="fas fa-search" aria-hidden="true"></i><input id="staff-search" name="q" value="{{ request('q') }}" maxlength="150" placeholder="Nama atau email anggota tim"></div></div><div><label for="staff-active">Status akun</label><select name="active" id="staff-active" class="form-select"><option value="">Semua status</option><option value="1" @selected(request('active') === '1')>Aktif</option><option value="0" @selected(request('active') === '0')>Nonaktif</option></select></div><button class="dh-button">Terapkan</button>@if(request()->hasAny(['q','active']))<a class="dh-reset" href="{{ route('administration.staff') }}">Reset</a>@endif</form>
<div class="dh-team-grid">
@forelse($staff as $member)
<article class="dh-member"><div class="d-flex justify-content-between align-items-start"><span class="dh-avatar dh-avatar-large">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span><span class="dh-status {{ $member->is_active ? 'dh-status-resolved' : '' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span></div><h2>{{ $member->name }}</h2><p>{{ $member->isDepartmentHead() ? 'Kepala Dinas' : 'Staf lapangan' }}</p><a class="dh-member-email" href="mailto:{{ $member->email }}">{{ $member->email }}</a><div class="dh-member-stats"><div><strong>{{ $member->active_reports_count }}</strong><small>Laporan aktif</small></div><div><strong>{{ $member->active_complaints_count }}</strong><small>Keluhan aktif</small></div></div><button class="dh-button dh-button-outline w-100" data-bs-toggle="modal" data-bs-target="#member{{ $member->id }}">Lihat profil <i class="fas fa-arrow-right" aria-hidden="true"></i></button></article>
@empty
<div class="dh-empty"><i class="fas fa-users" aria-hidden="true"></i><h3>Anggota tim tidak ditemukan</h3><p>Coba kata kunci lain atau ubah status akun.</p></div>
@endforelse
</div><div class="dh-pagination">{{ $staff->links('pagination::bootstrap-5') }}</div></section>
<p class="dh-footnote"><i class="fas fa-circle-info" aria-hidden="true"></i> Penambahan anggota dan perubahan akun dikelola oleh administrator.</p>
</div>
@foreach($staff as $member)
@push('modals')
<div class="modal fade" id="member{{ $member->id }}" tabindex="-1" aria-labelledby="memberTitle{{ $member->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title fs-5" id="memberTitle{{ $member->id }}">Profil anggota tim</h2><button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><span class="dh-avatar dh-avatar-large mb-3">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span><h3 class="fs-5">{{ $member->name }}</h3><p class="dh-muted">{{ $member->isDepartmentHead() ? 'Kepala Dinas' : 'Staf lapangan' }} · {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</p><dl class="dh-profile-details"><dt>Email</dt><dd>{{ $member->email }}</dd><dt>Telepon</dt><dd>{{ $member->phone ?: 'Belum ditambahkan' }}</dd><dt>Departemen</dt><dd>{{ $member->department?->name ?? 'Belum diatur' }}</dd><dt>Bergabung</dt><dd>{{ $member->created_at->translatedFormat('d M Y') }}</dd></dl></div><div class="modal-footer"><button class="dh-button dh-button-outline" data-bs-dismiss="modal">Tutup</button></div></div></div></div>
@endpush
@endforeach
@endsection
