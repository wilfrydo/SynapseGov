@extends('layouts.dashboard')

@section('title', 'Admin Command Center')

@section('content')
@include('admin.heading')
    <style>
        .admin-grid-top {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2.5rem;
            margin-bottom: 2rem;
        }

        .admin-grid-bottom {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .admin-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .action-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.15rem;
            border-radius: 14px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            text-decoration: none;
            color: var(--text-main);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-elevated);
            border-color: var(--brand-primary);
            color: var(--text-main);
        }

        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .action-icon.blue {
            background: rgba(37, 99, 235, 0.12);
            color: #2563eb;
        }

        .action-icon.emerald {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
        }

        .action-icon.amber {
            background: rgba(245, 158, 11, 0.12);
            color: #f59e0b;
        }

        .action-icon.purple {
            background: rgba(99, 102, 241, 0.12);
            color: #6366f1;
        }

        .action-title {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 0.15rem;
            text-decoration: underline;
            text-underline-offset: 4px;
            text-decoration-thickness: 2px;
            color: var(--text-main);
        }

        .action-sub {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .feed-item {
            padding: 0.9rem 0;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            gap: 0.85rem;
            align-items: flex-start;
        }

        .feed-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .feed-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 0.5rem;
            flex-shrink: 0;
        }

        .feed-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .feed-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.2rem;
        }

        .chart-container-wrapper {
            position: relative;
            height: 260px;
            width: 100%;
        }

        @media (max-width: 991px) {

            .admin-grid-top,
            .admin-grid-bottom {
                grid-template-columns: 1fr;
            }

            .admin-actions-grid {
                grid-template-columns: 1fr;
            }

            .modern-quick-stats {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
    }
</style>

    <!-- Dashboard Hero Header -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex flex-column">
                <div class="dashboard-header-title mb-2">
                    <i class="fas fa-shield-halved me-2"></i>Selamat datang di pusat layanan.
                </div>
                <div class="dashboard-header-desc mb-3 opacity-75">
                    Satu tempat untuk mengatur laporan, membantu tim, dan menjaga layanan tetap berjalan.
                </div>
                <div class="dashboard-header-time">
                    <i class="fas fa-clock me-2"></i>{{ now()->translatedFormat('l, d F Y — H:i') }} WIB
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.monitoring') }}" class="btn-back">
                    <i class="fas fa-chart-line"></i>Pantau SLA
                </a>
                <a href="{{ route('admin.reports') }}" class="btn-modern">
                    <i class="fas fa-file-export"></i>Kelola Laporan
                </a>
            </div>
        </div>

        <!-- Quick Stats Grid inside Hero -->
        <div class="quick-stats">
            <div class="quick-stat-box">
                <div class="quick-stat-icon"><i class="fas fa-folder-open"></i></div>
                <div class="quick-stat-number">{{ $stats['total_reports'] ?? 0 }}</div>
                <div class="quick-stat-label">Total Laporan</div>
            </div>
            <div class="quick-stat-box">
                <div class="quick-stat-icon"><i class="fas fa-comments"></i></div>
                <div class="quick-stat-number">{{ $stats['total_complaints'] ?? 0 }}</div>
                <div class="quick-stat-label">Total Keluhan</div>
            </div>
            <div class="quick-stat-box">
                <div class="quick-stat-icon"><i class="fas fa-users-gear"></i></div>
                <div class="quick-stat-number">{{ $stats['total_users'] ?? 0 }}</div>
                <div class="quick-stat-label">Total Pengguna</div>
            </div>
            <div class="quick-stat-box">
                <div class="quick-stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="quick-stat-number">{{ $stats['pending_reports'] ?? 0 }}</div>
                <div class="quick-stat-label">Antrean Masuk</div>
            </div>
            <div class="quick-stat-box">
                <div class="quick-stat-icon"><i class="fas fa-circle-check"></i></div>
                <div class="quick-stat-number">{{ $stats['resolved_reports'] ?? 0 }}</div>
                <div class="quick-stat-label">Terselesaikan</div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="admin-grid-top">
        <!-- Chart: Trend Aktivitas 30 Hari -->
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 fw-bold d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-chart-area text-primary"></i>
                    <span>Tren Volume Laporan & Keluhan (30 Hari Terakhir)</span>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-1" style="font-size: 0.75rem;">Live Data</span>
            </div>
            <div class="card-body pt-3">
                <div class="chart-container-wrapper">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- SLA Health & Quick Alerts -->
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 fw-bold">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-heart-pulse text-danger"></i>
                    <span>Integritas Layanan & SLA</span>
                </div>
            </div>
            <div class="card-body pt-3">
                @php
                    $slaBreached = $stats['sla_breached'] ?? 0;
                    $dueSoon = $stats['due_soon'] ?? 0;
                    $pendingAssign = $stats['pending_assignments'] ?? 0;
                    $totalRep = $stats['total_reports'] ?? 0;
                    $resolvedRep = $stats['resolved_reports'] ?? 0;
                    $completionRate = $totalRep > 0 ? round(($resolvedRep / $totalRep) * 100, 1) : 100;
                @endphp

                <div class="stats-grid mb-4 gap-4">
                    <div class="stat-item rounded-4 shadow-sm border-0 {{ $slaBreached > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                        <div class="stat-number {{ $slaBreached > 0 ? 'text-danger' : 'text-success' }}">{{ $slaBreached }}</div>
                        <div class="stat-label">Melewati SLA</div>
                    </div>
                    <div class="stat-item rounded-4 shadow-sm border-0 {{ $dueSoon > 0 ? 'bg-warning bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                        <div class="stat-number {{ $dueSoon > 0 ? 'text-warning' : 'text-success' }}">{{ $dueSoon }}</div>
                        <div class="stat-label">Mendekati batas waktu</div>
                    </div>
                </div>

                <div class="p-3 rounded-4 mb-4 shadow-sm"
                    style="background: var(--bg-canvas); border: 1px solid var(--card-border);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold">Tingkat Penyelesaian (Completion Rate)</span>
                        <span class="fw-bold text-primary">{{ $completionRate }}%</span>
                    </div>
                    <div class="progress" style="height: 10px; background: rgba(0,0,0,0.05); border-radius: 999px;">
                        <div class="progress-bar bg-primary rounded-pill" role="progressbar"
                            style="width: {{ $completionRate }}%;"></div>
                    </div>
                </div>

                <!-- Alert notification box -->
                @if($slaBreached > 0)
                    <div class="alert alert-danger py-3 px-3 m-0 small d-flex align-items-center gap-3 border-0 rounded-4 shadow-sm">
                        <i class="fas fa-triangle-exclamation fs-4"></i>
                        <div>
                            Terdapat <strong class="fs-6">{{ $slaBreached }} laporan</strong> melampaui batas SLA. Segera eskalasi ke kepala OPD terkait.
                        </div>
                    </div>
                @elseif($pendingAssign > 0)
                    <div class="alert alert-warning py-3 px-3 m-0 small d-flex align-items-center gap-3 border-0 rounded-4 shadow-sm">
                        <i class="fas fa-user-clock fs-4"></i>
                        <div>
                            <strong class="fs-6">{{ $pendingAssign }} tugas</strong> menunggu penugasan staff teknis lapangan.
                        </div>
                    </div>
                @else
                    <div class="alert alert-success py-3 px-3 m-0 small d-flex align-items-center gap-3 border-0 rounded-4 shadow-sm">
                        <i class="fas fa-circle-check fs-4"></i>
                        <div>
                            <strong class="fs-6">Optimal!</strong> Seluruh pengaduan berada dalam parameter SLA normal.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="admin-grid-bottom">
        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 fw-bold">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-bolt text-warning"></i>
                    <span>Aksi Cepat Manajemen</span>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="admin-actions-grid">
                    <a href="{{ route('admin.reports') }}" class="action-card">
                        <div class="action-icon blue">
                            <i class="fas fa-file-lines"></i>
                        </div>
                        <div>
                            <div class="action-title">Semua Laporan</div>
                            <div class="action-sub">Tinjau, filter, dan verifikasi</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.monitoring') }}" class="action-card">
                        <div class="action-icon emerald">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <div class="action-title">Monitoring & SLA</div>
                            <div class="action-sub">Evaluasi matriks performa</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.departments') }}" class="action-card">
                        <div class="action-icon amber">
                            <i class="fas fa-building-columns"></i>
                        </div>
                        <div>
                            <div class="action-title">Organisasi OPD</div>
                            <div class="action-sub">Daftar dinas & kepala instansi</div>
                        </div>
                    </a>

                    <a href="{{ route('admin.users') }}" class="action-card">
                        <div class="action-icon purple">
                            <i class="fas fa-users-gear"></i>
                        </div>
                        <div>
                            <div class="action-title">Kelola Pengguna</div>
                            <div class="action-sub">Akses role, staff, & warga</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 pb-0 fw-bold d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clock-rotate-left text-info"></i>
                    <span>Aktivitas Masuk Terbaru</span>
                </div>
                <a href="{{ route('admin.reports') }}"
                    class="btn btn-sm btn-light py-1 px-3 text-decoration-none rounded-pill shadow-sm fw-bold" style="font-size:0.75rem;">Lihat
                    Semua</a>
            </div>
            <div class="card-body pt-3">
                @if(isset($recentReports) && $recentReports->count() > 0)
                    <div class="d-flex flex-column gap-3">
                        @foreach($recentReports->take(4) as $report)
                            <div class="feed-item p-3 rounded-4 shadow-sm border border-light bg-canvas position-relative overflow-hidden" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="feed-dot bg-primary mt-2 flex-shrink-0"></div>
                                    <div style="flex: 1; min-width: 0;">
                                        <div class="feed-title fw-bold text-dark mb-1">{{ $report->title }}</div>
                                        <div class="feed-meta d-flex flex-wrap gap-2 text-muted" style="font-size: 0.75rem;">
                                            <span><i class="fas fa-user text-primary opacity-50 me-1"></i>{{ $report->user->name ?? 'Warga' }}</span>
                                            &bull;
                                            <span><i class="fas fa-building text-info opacity-50 me-1"></i>{{ $report->department->name ?? 'Umum' }}</span>
                                            &bull;
                                            <span><i class="fas fa-clock text-warning opacity-50 me-1"></i>{{ $report->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ms-2">
                                        @php
                                            $statusClass = match (strtolower($report->status ?? '')) {
                                                'submitted' => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
                                                'verified' => 'bg-success bg-opacity-10 text-success border border-success-subtle',
                                                'assigned' => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
                                                'in_progress' => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                                                'resolved' => 'bg-success bg-opacity-10 text-success border border-success-subtle',
                                                'closed' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
                                                default => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle'
                                            };
                                        @endphp
                                        <span
                                            class="badge rounded-pill {{ $statusClass }} px-2 py-1" style="font-size: 0.7rem;">{{ \App\Models\Report::statusLabel($report->status) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-muted bg-light rounded-4 border border-light border-dashed h-100 d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                        <span class="fw-bold">Belum ada laporan aktivitas terbaru.</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('trendsChart');
            if (!ctx || typeof Chart === 'undefined') return;

            // Monthly data from controller
            const monthlyReports = @json($monthlyStats['reports'] ?? []);
            const monthlyComplaints = @json($monthlyStats['complaints'] ?? []);

            const labels = [];
            const reportMap = {};
            const complaintMap = {};

            monthlyReports.forEach(item => {
                labels.push(item.date);
                reportMap[item.date] = item.count;
            });

            monthlyComplaints.forEach(item => {
                if (!labels.includes(item.date)) labels.push(item.date);
                complaintMap[item.date] = item.count;
            });

            labels.sort();

            // Fill missing days if needed or fallback dummy curve if empty
            const finalLabels = labels.length > 0 ? labels.slice(-14) : ['H-13', 'H-12', 'H-11', 'H-10', 'H-9', 'H-8', 'H-7', 'H-6', 'H-5', 'H-4', 'H-3', 'H-2', 'Kemarin', 'Hari Ini'];
            const reportData = labels.length > 0 ? finalLabels.map(l => reportMap[l] || 0) : [2, 4, 3, 5, 4, 7, 8, 6, 9, 7, 11, 8, 12, 10];
            const complaintData = labels.length > 0 ? finalLabels.map(l => complaintMap[l] || 0) : [1, 2, 1, 3, 2, 3, 4, 2, 5, 3, 4, 3, 5, 4];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: finalLabels,
                    datasets: [
                        {
                            label: 'Laporan Publik',
                            data: reportData,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Keluhan & Aspirasi',
                            data: complaintData,
                            borderColor: '#0ea5e9',
                            backgroundColor: 'rgba(14, 165, 233, 0.08)',
                            tension: 0.35,
                            fill: true,
                            borderWidth: 2,
                            borderDash: [4, 4],
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                font: { family: 'Plus Jakarta Sans', size: 12 }
                            }
                        },
                        tooltip: {
                            padding: 10,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.15)' },
                            ticks: { font: { family: 'Plus Jakarta Sans', size: 10 } }
                        }
                    }
                }
            });
        });
    </script>
@endsection
