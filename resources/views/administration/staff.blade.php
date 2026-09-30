@extends('layouts.dashboard')
@section('title', 'Tim Departemen')
@section('content')
@php
    $total = $staff->total();
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Tim Departemen', 'description' => 'Kenali anggota tim dan pantau rekan kerja Anda.'])

<div class="dh-team-summary">
    <span><strong>{{ $total }}</strong> anggota tim</span>
</div>

<section class="dh-panel">
    <div class="dh-team-grid mt-3">
        @forelse($staff as $member)
        <article class="dh-member">
            <div class="d-flex justify-content-between align-items-start">
                <span class="dh-avatar dh-avatar-large">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span>
                <span class="dh-status dh-status-resolved">Aktif</span>
            </div>
            <h2>{{ $member->name }}</h2>
            <p>{{ $member->isDepartmentHead() ? 'Kepala Departemen' : 'Staf' }}</p>
            <a class="dh-member-email" href="mailto:{{ $member->email }}">{{ $member->email }}</a>
            <div class="dh-member-stats">
                <div>
                    <strong>-</strong>
                    <small>Tugas</small>
                </div>
            </div>
            <button class="dh-button dh-button-outline w-100" data-bs-toggle="modal" data-bs-target="#member{{ $member->id }}">Lihat profil <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        </article>
        @empty
        <div class="dh-empty">
            <i class="fas fa-users" aria-hidden="true"></i>
            <h3>Anggota tim tidak ditemukan</h3>
            <p>Belum ada staf yang terdaftar.</p>
        </div>
        @endforelse
    </div>
    
    <div class="dh-pagination mt-4">
        {{ $staff->links('pagination::bootstrap-5') }}
    </div>
</section>

</div>

@foreach($staff as $member)
@push('modals')
<div class="modal fade" id="member{{ $member->id }}" tabindex="-1" aria-labelledby="memberTitle{{ $member->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="memberTitle{{ $member->id }}">Profil anggota tim</h2>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <span class="dh-avatar dh-avatar-large mb-3">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span>
                <h3 class="fs-5">{{ $member->name }}</h3>
                <p class="dh-muted">{{ $member->isDepartmentHead() ? 'Kepala Departemen' : 'Staf' }} · Aktif</p>
                <dl class="dh-profile-details">
                    <dt>Email</dt><dd>{{ $member->email }}</dd>
                    <dt>Telepon</dt><dd>{{ $member->phone ?: 'Belum ditambahkan' }}</dd>
                    <dt>Bergabung</dt><dd>{{ $member->created_at->translatedFormat('d M Y') }}</dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button class="dh-button dh-button-outline" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endpush
@endforeach
@endsection
