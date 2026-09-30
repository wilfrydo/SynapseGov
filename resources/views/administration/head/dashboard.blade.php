@extends('layouts.dashboard')
@section('title', 'Ringkasan Departemen')
@section('content')
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
    $priorityLabels = \App\Models\Report::PRIORITY_LABELS;
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Selamat datang, '.auth()->user()->name.'.', 'description' => 'Lihat perkembangan layanan dan tentukan langkah tim hari ini.'])
<section class="dh-welcome">
    <div><span class="dh-eyebrow">LAYANAN PUBLIK, LEBIH TERARAH</span><h2>Tim yang terhubung.<br>Pelayanan yang lebih baik.</h2><p>Pantau progres, atur penugasan, dan bantu setiap laporan<br class="d-none d-lg-block"> mendapatkan tindak lanjut yang tepat.</p><a class="dh-button dh-button-white" href="{{ route('administration.reports') }}">Kelola laporan <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    <div class="dh-welcome-mark" aria-hidden="true"><div class="dh-orbit"><i class="fas fa-building-columns"></i></div><span>SynapseGov / {{ now()->format('Y') }}</span></div>
</section>
<div class="dh-metrics">
@foreach([['total','Total laporan','fa-file-lines','Seluruh laporan departemen',[]],['assign','Siap ditugaskan','fa-user-plus','Tentukan staf penanggung jawab',['status'=>'verified']],['active','Sedang ditangani','fa-arrows-spin','Penugasan dan tindak lanjut',[]],['done','Sudah selesai','fa-circle-check','Laporan selesai dan ditutup',[]]] as [$key,$label,$icon,$hint,$params])
<div class="dh-metric"><div class="dh-metric-top"><span>{{ $label }}</span><i class="fas {{ $icon }}" aria-hidden="true"></i></div><strong>{{ number_format($metrics[$key]) }}</strong><small>{{ $hint }}</small></div>
@endforeach
</div>
<div class="dh-columns">
<section class="dh-panel"><div class="dh-panel-heading"><div><h2>Laporan terbaru</h2><p>Perkembangan terbaru dari masyarakat.</p></div><a href="{{ route('administration.reports') }}">Lihat semua <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
@forelse($recentReports as $report)
<a class="dh-report-row" href="{{ route('administration.reports', ['q' => $report->ticket_no]) }}"><span class="dh-file-icon"><i class="far fa-file-lines" aria-hidden="true"></i></span><div class="dh-row-main"><small>{{ $report->ticket_no }} · {{ $report->created_at->translatedFormat('d M Y') }}</small><h3>{{ $report->title }}</h3><span>{{ $report->assignedUser?->name ?? 'Belum ada penanggung jawab' }}</span></div><span class="dh-status dh-status-{{ $report->status }}">{{ $statusLabels[$report->status] ?? $report->status }}</span></a>
@empty
<div class="dh-empty"><i class="far fa-folder-open" aria-hidden="true"></i><h3>Belum ada laporan</h3><p>Laporan untuk departemen Anda akan tampil di sini.</p></div>
@endforelse
</section>
<div class="dh-side-panels"><section class="dh-panel dh-attention"><span class="dh-eyebrow">PERLU PERHATIAN</span><h2>{{ $metrics['overdue'] }} laporan melewati SLA</h2><p>Tinjau kendala tim agar layanan kembali berjalan tepat waktu.</p><a href="{{ route('administration.reports', ['overdue' => 1]) }}">Tinjau batas waktu <i class="fas fa-arrow-right" aria-hidden="true"></i></a></section>
<section class="dh-panel"><div class="dh-panel-heading"><div><h2>Aktivitas tim</h2><p>Beban laporan staf aktif.</p></div><a href="{{ route('administration.staff') }}" aria-label="Lihat seluruh tim"><i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
@forelse($team as $member)
<div class="dh-team-row"><span class="dh-avatar">{{ Str::upper(Str::substr($member->name, 0, 1)) }}</span><div class="dh-row-main"><strong>{{ $member->name }}</strong><small>Staf lapangan</small></div><span class="dh-task-count">{{ $member->active_reports_count }} tugas</span></div>
@empty
<div class="dh-empty"><p>Belum ada staf aktif di departemen ini.</p></div>
@endforelse
</section></div></div>
</div>
@endsection
