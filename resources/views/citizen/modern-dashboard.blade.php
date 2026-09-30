@extends('layouts.dashboard')
@section('title', 'Dashboard Warga')
@push('styles')
<style>
    .cz * { text-decoration: none !important; }

    /* ── Hero Banner ── */
    .cz-hero {
        background: linear-gradient(135deg, #852735 0%, #6b1d2a 60%, #4a1520 100%);
        border-radius: 20px;
        padding: 44px 42px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 40px;
        position: relative;
        overflow: hidden;
        margin-bottom: 28px;
    }
    .cz-hero::before {
        content: '';
        position: absolute;
        top: -80px; right: -60px;
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(255,255,255,.06) 0%, transparent 70%);
        border-radius: 50%;
    }
    .cz-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; left: 30%;
        width: 240px; height: 240px;
        background: radial-gradient(circle, rgba(255,255,255,.04) 0%, transparent 70%);
        border-radius: 50%;
    }
    .cz-hero-content { position: relative; z-index: 2; }
    .cz-hero-eyebrow {
        font-size: 10px; font-weight: 700; letter-spacing: 2.5px;
        color: rgba(255,255,255,.55); margin-bottom: 16px;
    }
    .cz-hero h1 {
        font-size: 32px; font-weight: 800; color: #fff;
        line-height: 1.2; letter-spacing: -1px; margin: 0 0 12px;
    }
    .cz-hero h1 span { color: #f5c6cb; }
    .cz-hero p {
        font-size: 14px; color: rgba(255,255,255,.75);
        line-height: 1.8; margin: 0 0 24px; max-width: 460px;
    }
    .cz-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .cz-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 12px 22px; border-radius: 10px; font-size: 13px;
        font-weight: 650; border: none; cursor: pointer;
        transition: all .2s ease;
    }
    .cz-btn-white { background: #fff; color: #852735; }
    .cz-btn-white:hover { background: #f9e9ed; color: #852735; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.15); }
    .cz-btn-ghost { background: rgba(255,255,255,.12); color: #fff; border: 1px solid rgba(255,255,255,.25); }
    .cz-btn-ghost:hover { background: rgba(255,255,255,.2); color: #fff; transform: translateY(-2px); }
    .cz-hero-visual {
        position: relative; z-index: 2;
        width: 180px; height: 180px; flex-shrink: 0;
        display: grid; place-items: center;
    }
    .cz-hero-ring {
        width: 160px; height: 160px;
        border: 1.5px solid rgba(255,255,255,.15);
        border-radius: 50%; display: grid; place-items: center;
        box-shadow: 0 0 0 20px rgba(255,255,255,.04), 0 0 0 40px rgba(255,255,255,.02);
    }
    .cz-hero-ring i { font-size: 52px; color: rgba(255,255,255,.2); }
    .cz-hero-label {
        position: absolute; bottom: -4px; left: 50%; transform: translateX(-50%);
        font-size: 9px; letter-spacing: 3px; color: rgba(255,255,255,.3); white-space: nowrap;
    }

    /* ── Stat Cards ── */
    .cz-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
    .cz-stat {
        background: var(--card-bg); border: 1px solid var(--card-border);
        border-radius: 14px; padding: 22px 20px;
        position: relative; overflow: hidden;
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .cz-stat:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.06); }
    .cz-stat-top {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 14px;
    }
    .cz-stat-top span { font-size: 12px; color: var(--text-muted); font-weight: 600; }
    .cz-stat-icon {
        width: 36px; height: 36px; border-radius: 10px;
        display: grid; place-items: center; font-size: 14px;
    }
    .cz-stat-icon.red { background: #fcebed; color: #9f2434; }
    .cz-stat-icon.blue { background: #eaf0ff; color: #3b5ea6; }
    .cz-stat-icon.green { background: #e5f5ed; color: #1a7a4c; }
    .cz-stat-icon.amber { background: #fff3d9; color: #8a6018; }
    .dark .cz-stat-icon.red { background: #3d1f24; color: #f5aab5; }
    .dark .cz-stat-icon.blue { background: #1e2a42; color: #8fadde; }
    .dark .cz-stat-icon.green { background: #1a3328; color: #6cc99e; }
    .dark .cz-stat-icon.amber { background: #33291a; color: #e0b96a; }
    .cz-stat strong {
        display: block; font-size: 30px; font-weight: 800;
        letter-spacing: -1px; color: var(--text-main); line-height: 1;
    }
    .cz-stat small { font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; }

    /* ── Content Grid ── */
    .cz-grid { display: grid; grid-template-columns: 1.7fr 1fr; gap: 22px; align-items: start; }

    /* ── Panel ── */
    .cz-panel {
        background: var(--card-bg); border: 1px solid var(--card-border);
        border-radius: 14px; overflow: hidden;
    }
    .cz-panel-head {
        padding: 20px 24px; display: flex; justify-content: space-between;
        align-items: center; border-bottom: 1px solid var(--card-border);
    }
    .cz-panel-head h2 { font-size: 15px; font-weight: 700; margin: 0; color: var(--text-main); }
    .cz-panel-head p { font-size: 11px; color: var(--text-muted); margin: 4px 0 0; }
    .cz-panel-link {
        font-size: 12px; font-weight: 700; color: var(--brand-primary);
        display: inline-flex; align-items: center; gap: 6px;
    }
    .cz-panel-link:hover { gap: 10px; }

    /* ── Report Card Items ── */
    .cz-ticket {
        display: flex; align-items: flex-start; gap: 16px;
        padding: 20px 24px;
        border-bottom: 1px solid var(--card-border);
        transition: background .15s ease;
        color: var(--text-main);
    }
    .cz-ticket:last-child { border-bottom: none; }
    .cz-ticket:hover { background: var(--bg-canvas); }
    .cz-ticket-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: grid; place-items: center; flex-shrink: 0;
        font-size: 16px;
    }
    .cz-ticket-icon.report { background: #eaf0ff; color: #4a6fbd; }
    .cz-ticket-icon.complaint { background: #fff3d9; color: #9a7020; }
    .dark .cz-ticket-icon.report { background: #1e2a42; color: #8fadde; }
    .dark .cz-ticket-icon.complaint { background: #33291a; color: #e0b96a; }
    .cz-ticket-body { flex: 1; min-width: 0; }
    .cz-ticket-meta {
        font-size: 11px; color: var(--text-muted); margin-bottom: 4px;
        display: flex; align-items: center; gap: 6px;
    }
    .cz-ticket-meta .cz-dot { width: 3px; height: 3px; border-radius: 50%; background: var(--text-muted); }
    .cz-ticket-title {
        font-size: 14px; font-weight: 700; color: var(--text-main);
        margin: 0 0 6px; line-height: 1.5; overflow-wrap: anywhere;
    }
    .cz-ticket-dept {
        font-size: 11px; color: var(--text-muted);
        display: inline-flex; align-items: center; gap: 5px;
    }
    .cz-ticket-dept i { font-size: 10px; }
    .cz-ticket-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
    .cz-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 11px; border-radius: 8px;
        font-size: 11px; font-weight: 650; white-space: nowrap;
    }
    .cz-badge::before {
        content: ''; width: 6px; height: 6px; border-radius: 50%;
    }
    .cz-badge-new { background: #fff3d9; color: #846016; }
    .cz-badge-new::before { background: #d4a017; }
    .cz-badge-progress { background: #eaf0ff; color: #3b5ea6; }
    .cz-badge-progress::before { background: #4a6fbd; animation: cz-pulse 1.5s infinite; }
    .cz-badge-done { background: #e5f5ed; color: #1a7a4c; }
    .cz-badge-done::before { background: #22a85f; }
    .cz-badge-reject { background: #fcebed; color: #a52940; }
    .cz-badge-reject::before { background: #d63c55; }
    .cz-badge-closed { background: var(--bg-canvas); color: var(--text-muted); }
    .cz-badge-closed::before { background: var(--text-muted); }
    @keyframes cz-pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
    .cz-ticket-arrow {
        font-size: 11px; color: var(--text-muted);
        opacity: 0; transition: opacity .15s, transform .15s;
    }
    .cz-ticket:hover .cz-ticket-arrow { opacity: 1; transform: translateX(3px); }

    /* ── Compact Complaint Row ── */
    .cz-complaint-row {
        display: flex; align-items: center; gap: 14px;
        padding: 16px 22px; border-bottom: 1px solid var(--card-border);
        transition: background .15s; color: var(--text-main);
    }
    .cz-complaint-row:last-child { border-bottom: none; }
    .cz-complaint-row:hover { background: var(--bg-canvas); }
    .cz-complaint-dot {
        width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
    }
    .cz-complaint-dot.investigating { background: #4a6fbd; }
    .cz-complaint-dot.submitted, .cz-complaint-dot.pending { background: #d4a017; }
    .cz-complaint-dot.resolved, .cz-complaint-dot.closed { background: #22a85f; }
    .cz-complaint-dot.rejected { background: #d63c55; }
    .cz-complaint-body { flex: 1; min-width: 0; }
    .cz-complaint-title {
        font-size: 13px; font-weight: 700; color: var(--text-main);
        margin: 0 0 2px; overflow-wrap: anywhere;
    }
    .cz-complaint-date { font-size: 10px; color: var(--text-muted); }
    .cz-complaint-status { font-size: 10px; font-weight: 600; color: var(--text-muted); white-space: nowrap; }

    /* ── Info Card ── */
    .cz-info {
        background: linear-gradient(145deg, #fdf8f0 0%, #fef6e8 100%);
        border: 1px solid #efe1c8;
        border-radius: 14px; padding: 28px 24px;
    }
    .dark .cz-info { background: linear-gradient(145deg, #2a2418 0%, #302820 100%); border-color: #4a3d2a; }
    .cz-info-eyebrow { font-size: 10px; font-weight: 700; letter-spacing: 2px; color: #9a7020; margin-bottom: 12px; }
    .dark .cz-info-eyebrow { color: #d0a850; }
    .cz-info h3 { font-size: 18px; font-weight: 750; color: #5a3d0a; margin: 0 0 10px; letter-spacing: -.3px; }
    .dark .cz-info h3 { color: #ebd6a8; }
    .cz-info p { font-size: 12px; color: #806840; line-height: 1.8; margin: 0 0 18px; }
    .dark .cz-info p { color: #c4a870; }
    .cz-info-links { display: flex; flex-direction: column; gap: 8px; }
    .cz-info-link {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 12px; font-weight: 650; color: #9a7020;
    }
    .cz-info-link i { font-size: 10px; }
    .dark .cz-info-link { color: #d0a850; }
    .cz-info-link:hover { gap: 12px; }

    /* ── Quick Actions ── */
    .cz-side-gap { display: grid; gap: 18px; }
    .cz-quick-actions {
        background: var(--card-bg); border: 1px solid var(--card-border);
        border-radius: 14px; padding: 22px;
    }
    .cz-quick-actions h3 {
        font-size: 13px; font-weight: 700; color: var(--text-main);
        margin: 0 0 14px; display: flex; align-items: center; gap: 8px;
    }
    .cz-quick-actions h3 i { color: var(--brand-primary); font-size: 12px; }
    .cz-action-btns { display: grid; gap: 8px; }
    .cz-action-btn {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 16px; border-radius: 10px;
        border: 1px solid var(--card-border); background: var(--bg-canvas);
        font-size: 12px; font-weight: 600; color: var(--text-main);
        transition: all .15s ease; cursor: pointer;
    }
    .cz-action-btn:hover {
        border-color: var(--brand-primary); background: var(--card-bg);
        color: var(--brand-primary); transform: translateX(4px);
    }
    .cz-action-btn i { font-size: 14px; width: 20px; text-align: center; color: var(--brand-primary); }

    /* ── Empty State ── */
    .cz-empty {
        padding: 48px 24px; text-align: center;
    }
    .cz-empty-icon {
        width: 56px; height: 56px; border-radius: 16px;
        background: var(--bg-canvas); display: inline-grid; place-items: center;
        font-size: 22px; color: var(--text-muted); margin-bottom: 16px;
    }
    .cz-empty h4 { font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0 0 6px; }
    .cz-empty p { font-size: 12px; color: var(--text-muted); margin: 0 0 18px; }

    /* ── Responsive ── */
    @media (max-width: 1100px) { .cz-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .cz-stats { grid-template-columns: 1fr 1fr; }
        .cz-hero { flex-direction: column; padding: 32px 26px; text-align: center; }
        .cz-hero h1 { font-size: 26px; }
        .cz-hero p { max-width: 100%; }
        .cz-hero-actions { justify-content: center; }
        .cz-hero-visual { display: none; }
    }
    @media (max-width: 480px) {
        .cz-stats { grid-template-columns: 1fr; }
        .cz-ticket { flex-wrap: wrap; }
        .cz-ticket-right { flex-direction: row; width: 100%; margin-top: 4px; }
    }
</style>
@endpush
@section('content')
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
    $badgeClass = function($status) {
        if (in_array($status, ['submitted','pending','verified','awaiting_info','awaiting_admin_approval'])) return 'cz-badge-new';
        if (in_array($status, ['assigned','in_progress','investigating','reviewed','needs_revision'])) return 'cz-badge-progress';
        if (in_array($status, ['resolved','closed'])) return 'cz-badge-done';
        if ($status === 'rejected') return 'cz-badge-reject';
        return 'cz-badge-closed';
    };
@endphp

<div class="cz">

{{-- ── Hero Banner ── --}}
<section class="cz-hero">
    <div class="cz-hero-content">
        <div class="cz-hero-eyebrow">PORTAL WARGA · SYNAPSEGOV</div>
        <h1>Selamat datang,<br><span>{{ auth()->user()->name }}.</span></h1>
        <p>Pantau status laporan Anda secara real-time dan bantu mewujudkan pelayanan masyarakat yang transparan.</p>
        <div class="cz-hero-actions">
            <a class="cz-btn cz-btn-white" href="{{ route('citizen.reports.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Buat Laporan</a>
            <a class="cz-btn cz-btn-ghost" href="{{ route('citizen.complaints.create') }}"><i class="fas fa-bullhorn" aria-hidden="true"></i> Sampaikan Keluhan</a>
        </div>
    </div>
    <div class="cz-hero-visual">
        <div class="cz-hero-ring"><i class="fas fa-city"></i></div>
        <span class="cz-hero-label">SYNAPSEGOV / {{ now()->format('Y') }}</span>
    </div>
</section>

{{-- ── Stat Cards ── --}}
<div class="cz-stats">
    @foreach([
        ['my_reports','Total Laporan','fa-file-lines','red','Laporan yang Anda buat'],
        ['in_progress_reports','Dalam Proses','fa-arrows-spin','blue','Sedang ditangani petugas'],
        ['resolved_reports','Selesai','fa-circle-check','green','Berhasil diselesaikan'],
        ['my_complaints','Total Keluhan','fa-comment-dots','amber','Aspirasi yang disampaikan']
    ] as [$key, $label, $icon, $color, $hint])
    <div class="cz-stat">
        <div class="cz-stat-top">
            <span>{{ $label }}</span>
            <div class="cz-stat-icon {{ $color }}"><i class="fas {{ $icon }}"></i></div>
        </div>
        <strong>{{ number_format($stats[$key] ?? 0) }}</strong>
        <small>{{ $hint }}</small>
    </div>
    @endforeach
</div>

{{-- ── Main Content Grid ── --}}
<div class="cz-grid">
    {{-- Left: Reports --}}
    <div class="cz-panel">
        <div class="cz-panel-head">
            <div>
                <h2>Laporan Terbaru</h2>
                <p>Status terkini laporan yang Anda ajukan</p>
            </div>
            <a class="cz-panel-link" href="{{ route('citizen.reports.index') }}">Lihat semua <i class="fas fa-arrow-right"></i></a>
        </div>

        @forelse($myReports as $report)
        <a class="cz-ticket" href="{{ route('citizen.reports.show', $report->id) }}">
            <div class="cz-ticket-icon report"><i class="far fa-file-lines"></i></div>
            <div class="cz-ticket-body">
                <div class="cz-ticket-meta">
                    <span>{{ $report->ticket_no }}</span>
                    <span class="cz-dot"></span>
                    <span>{{ $report->created_at->translatedFormat('d M Y') }}</span>
                </div>
                <h3 class="cz-ticket-title">{{ $report->title }}</h3>
                <span class="cz-ticket-dept"><i class="fas fa-building"></i> {{ $report->department?->name ?? 'Umum' }}</span>
            </div>
            <div class="cz-ticket-right">
                <span class="cz-badge {{ $badgeClass($report->status) }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
                <span class="cz-ticket-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
        </a>
        @empty
        <div class="cz-empty">
            <div class="cz-empty-icon"><i class="far fa-folder-open"></i></div>
            <h4>Belum ada laporan</h4>
            <p>Anda belum pernah membuat laporan apapun.</p>
            <a class="cz-btn cz-btn-white" style="background:#9f2434;color:#fff;display:inline-flex;" href="{{ route('citizen.reports.create') }}"><i class="fas fa-plus"></i> Buat laporan pertama</a>
        </div>
        @endforelse
    </div>

    {{-- Right Side --}}
    <div class="cz-side-gap">
        {{-- Complaints --}}
        <div class="cz-panel">
            <div class="cz-panel-head">
                <div>
                    <h2>Keluhan Anda</h2>
                    <p>Aspirasi dan keluhan terbaru</p>
                </div>
                <a class="cz-panel-link" href="{{ route('citizen.complaints.index') }}"><i class="fas fa-arrow-right"></i></a>
            </div>

            @forelse($myComplaints->take(5) as $complaint)
            <a class="cz-complaint-row" href="{{ route('citizen.complaints.show', $complaint->id) }}">
                <span class="cz-complaint-dot {{ $complaint->status }}"></span>
                <div class="cz-complaint-body">
                    <h4 class="cz-complaint-title">{{ $complaint->title }}</h4>
                    <span class="cz-complaint-date">{{ $complaint->created_at->translatedFormat('d M Y') }}</span>
                </div>
                <span class="cz-complaint-status">{{ $statusLabels[$complaint->status] ?? $complaint->status }}</span>
            </a>
            @empty
            <div class="cz-empty" style="padding: 32px 20px;">
                <div class="cz-empty-icon"><i class="far fa-comment-dots"></i></div>
                <h4>Belum ada keluhan</h4>
                <p>Sampaikan aspirasi Anda.</p>
            </div>
            @endforelse
        </div>

        {{-- Quick Actions --}}
        <div class="cz-quick-actions">
            <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
            <div class="cz-action-btns">
                <a class="cz-action-btn" href="{{ route('citizen.reports.create') }}"><i class="fas fa-file-circle-plus"></i> Buat Laporan Baru</a>
                <a class="cz-action-btn" href="{{ route('citizen.complaints.create') }}"><i class="fas fa-comment-medical"></i> Sampaikan Keluhan</a>
                <a class="cz-action-btn" href="{{ route('citizen.reports.index') }}"><i class="fas fa-list-check"></i> Lihat Semua Laporan</a>
            </div>
        </div>

        {{-- Info --}}
        <div class="cz-info">
            <div class="cz-info-eyebrow">BANTUAN & INFORMASI</div>
            <h3>Punya pertanyaan?</h3>
            <p>Hubungi pusat bantuan atau baca panduan pelaporan untuk informasi lebih lanjut tentang cara menggunakan SynapseGov.</p>
            <div class="cz-info-links">
                <a class="cz-info-link" href="{{ route('citizen.reports.create') }}"><i class="fas fa-book-open"></i> Panduan pelaporan</a>
                <a class="cz-info-link" href="{{ route('citizen.complaints.create') }}"><i class="fas fa-headset"></i> Hubungi layanan bantuan</a>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
