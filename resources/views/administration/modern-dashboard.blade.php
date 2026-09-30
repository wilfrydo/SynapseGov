@extends('layouts.dashboard')
@section('title', auth()->user()->isDepartmentHead() ? 'Department Head Dashboard' : 'Staff Dashboard')
@push('styles')
<style>
    .dh-workspace a,
    .dh-workspace a:hover,
    .dh-workspace a:focus,
    .dh-workspace a:active,
    .dh-workspace a *,
    .dh-panel a,
    .dh-panel a *,
    .dh-report-row,
    .dh-report-row:hover,
    .dh-report-row *,
    .dh-panel-heading a,
    .dh-status,
    .dh-welcome a,
    .dh-button {
        text-decoration: none !important;
    }
</style>
@endpush
@section('content')
<style>
    .dh-workspace a, .dh-workspace a *, .dh-report-row, .dh-report-row *, .dh-panel-heading a, .dh-status {
        text-decoration: none !important;
    }
</style>
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Selamat datang, '.auth()->user()->name.'.', 'description' => 'Lihat perkembangan layanan dan tindak lanjuti tugas Anda hari ini.'])
<section class="dh-welcome">
    <div>
        <span class="dh-eyebrow">WORKSPACE STAFF</span>
        <h2>Layanan terpadu.<br>Kinerja yang lebih baik.</h2>
        <p>Pantau laporan yang ditugaskan kepada Anda, selesaikan keluhan,<br class="d-none d-lg-block"> dan berikan pelayanan terbaik untuk masyarakat.</p>
        <a class="dh-button dh-button-white text-decoration-none" style="text-decoration: none !important;" href="{{ route('administration.reports') }}">Lihat tugas saya <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="dh-welcome-mark" aria-hidden="true"><div class="dh-orbit"><i class="fas fa-user-check"></i></div><span>SynapseGov / {{ now()->format('Y') }}</span></div>
</section>

<div class="dh-metrics">
@foreach([
    ['total_reports','Total penugasan','fa-file-lines','Seluruh laporan untuk Anda'],
    ['pending_action','Perlu tindakan','fa-bell','Laporan yang butuh respons Anda'],
    ['in_progress_reports','Sedang ditangani','fa-arrows-spin','Laporan dalam pengerjaan'],
    ['resolved_reports','Sudah selesai','fa-circle-check','Laporan yang telah Anda tutup']
] as [$key, $label, $icon, $hint])
<div class="dh-metric">
    <div class="dh-metric-top"><span>{{ $label }}</span><i class="fas {{ $icon }}" aria-hidden="true"></i></div>
    <strong>{{ number_format($stats[$key] ?? 0) }}</strong>
    <small>{{ $hint }}</small>
</div>
@endforeach
</div>

<div class="dh-columns">
    <section class="dh-panel">
        <div class="dh-panel-heading">
            <div>
                <h2>Laporan terbaru</h2>
                <p>Perkembangan terbaru dari laporan yang masuk ke departemen Anda.</p>
            </div>
            <a href="{{ route('administration.reports') }}" class="text-decoration-none" style="text-decoration: none !important;">Lihat semua <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        
        @forelse($departmentReports as $report)
        <a class="dh-report-row text-decoration-none" style="text-decoration: none !important;" href="{{ route('administration.reports', ['q' => $report->ticket_no]) }}">
            <span class="dh-file-icon"><i class="far fa-file-lines" aria-hidden="true"></i></span>
            <div class="dh-row-main">
                <small>{{ $report->ticket_no }} · {{ $report->created_at->translatedFormat('d M Y') }}</small>
                <h3>{{ $report->title }}</h3>
                <span>{{ $report->assignedUser?->name ?? 'Belum ada penanggung jawab' }}</span>
            </div>
            <span class="dh-status dh-status-{{ $report->status }}" style="text-decoration: none !important;">{{ $statusLabels[$report->status] ?? $report->status }}</span>
        </a>
        @empty
        <div class="dh-empty">
            <i class="far fa-folder-open" aria-hidden="true"></i>
            <h3>Belum ada laporan</h3>
            <p>Laporan yang ditugaskan kepada Anda akan tampil di sini.</p>
        </div>
        @endforelse
    </section>

    <div class="dh-side-panels">
        <section class="dh-panel dh-attention">
            <span class="dh-eyebrow">STATISTIK KELUHAN</span>
            <h2>{{ $stats['pending_complaints'] ?? 0 }} keluhan baru</h2>
            <p>Tinjau dan tindak lanjuti keluhan masyarakat yang masuk ke departemen Anda.</p>
            <a href="{{ route('administration.complaints') }}">Lihat keluhan <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </section>

        <section class="dh-panel">
            <div class="dh-panel-heading">
                <div>
                    <h2>Rekan Tim</h2>
                    <p>Staf aktif di {{ $department->name ?? 'Departemen' }}.</p>
                </div>
                <a href="{{ route('administration.staff') }}" aria-label="Lihat seluruh tim"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            @forelse($departmentStaff->take(5) as $member)
            <div class="dh-team-row">
                <span class="dh-avatar">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span>
                <div class="dh-row-main">
                    <strong>{{ $member->name }}</strong>
                    <small>{{ $member->isDepartmentHead() ? 'Kepala Departemen' : 'Staf' }}</small>
                </div>
            </div>
            @empty
            <div class="dh-empty"><p>Belum ada staf aktif di departemen ini.</p></div>
            @endforelse
        </section>
    </div>
</div>
</div>
@endsection
