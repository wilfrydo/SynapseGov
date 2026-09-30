@extends('layouts.dashboard')

@section('title', 'Laporan Admin')

@section('content')
@include('admin.heading')
<!-- Reports Container -->
<div class="card border-0 shadow-sm rounded-4 mb-4" style="overflow: visible !important;">
    <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-bottom-0 rounded-top-4">
        <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2 text-muted"></i>Daftar Laporan Masuk</h5>
        <span class="badge bg-light text-dark border rounded-pill px-3 py-2 shadow-sm fw-semibold" style="font-size: 0.85rem;">{{ $reports->total() }} Total Laporan</span>
    </div>
    
    <div class="card-body p-4 bg-light rounded-bottom-4" style="overflow: visible !important; padding-bottom: 2rem !important;">
        <div class="reports-list">
            @forelse($reports as $report)
            <div class="card report-card border-0 mb-3 rounded-4 shadow-sm bg-white overflow-visible" style="position: relative;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <!-- User & Ticket -->
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 border border-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 48px; height: 48px; font-weight: bold; font-size: 1.2rem;">
                                    {{ substr($report->user ? $report->user->name : 'N', 0, 1) }}
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark">{{ $report->user ? $report->user->name : 'N/A' }}</h6>
                                    <div class="text-muted small mb-1"><i class="fas fa-clock me-1 text-primary opacity-50"></i>{{ $report->created_at->translatedFormat('d M Y, H:i') }}</div>
                                    <small class="text-primary fw-bold" style="font-family: monospace; background: var(--bs-primary-bg-subtle, #cfe2ff); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--bs-primary-border-subtle, #b6d4fe);">{{ $report->ticket_no }}</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Report Details -->
                        <div class="col-md-4 mt-3 mt-md-0 px-md-3 border-start border-end border-light">
                            <h6 class="mb-2 fw-bold text-dark">{{ $report->title }}</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @php
                                    $priorityColor = 'text-secondary';
                                    if ($report->priority === 'urgent') $priorityColor = 'text-danger';
                                    elseif ($report->priority === 'high') $priorityColor = 'text-warning';
                                    elseif ($report->priority === 'medium') $priorityColor = 'text-info';
                                    elseif ($report->priority === 'low') $priorityColor = 'text-success';
                                @endphp
                                <span class="badge bg-light text-secondary border"><i class="fas fa-exclamation-circle me-1 {{ $priorityColor }} opacity-75"></i> Prioritas {{ \App\Models\Report::priorityLabel($report->priority) }}</span>
                                @if($report->department)
                                <span class="badge bg-light text-secondary border"><i class="fas fa-building me-1 text-primary opacity-75"></i>{{ $report->department->name }}</span>
                                @endif
                                <span class="badge bg-light text-secondary border"><i class="fas fa-tag me-1 text-success opacity-75"></i>{{ $report->category }}</span>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-2 mt-3 mt-md-0 text-md-center">
                            <div class="text-muted small mb-2 fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.7rem;">Status</div>
                            @php
                                $statusColor = 'secondary';
                                $iconClass = 'fa-circle';
                                if(in_array($report->status, ['resolved', 'closed'])) { $statusColor = 'success'; $iconClass = 'fa-check-circle'; }
                                elseif(in_array($report->status, ['rejected', 'needs_revision'])) { $statusColor = 'danger'; $iconClass = 'fa-times-circle'; }
                                elseif(in_array($report->status, ['in_progress', 'reviewed', 'assigned'])) { $statusColor = 'info'; }
                                elseif(in_array($report->status, ['submitted', 'pending', 'awaiting_info'])) { $statusColor = 'warning'; }
                            @endphp
                            <span class="badge bg-white text-dark border border-secondary-subtle rounded-pill px-3 py-2 shadow-sm">
                                <i class="fas {{ $iconClass }} me-1 text-{{ $statusColor }}"></i> {{ \App\Models\Report::statusLabel($report->status) }}
                            </span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-md-3 mt-3 mt-md-0 text-md-end">
                            <div class="d-flex justify-content-md-end align-items-center gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-pill px-3 border shadow-sm btn-hover-elevate" data-bs-toggle="modal" data-bs-target="#viewReportModal{{ $report->id }}" title="Lihat Detail">
                                    <i class="fas fa-eye me-1 opacity-75"></i> Detail
                                </button>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle border shadow-sm btn-hover-elevate d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" style="width: 32px; height: 32px;">
                                        <i class="fas fa-ellipsis-v text-secondary"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px; padding: 8px; margin-top: 8px;">
                                        @if($report->attachments && count($report->attachments) > 0)
                                        <li><a class="dropdown-item rounded-3 py-2" href="{{ route('files.view', ['report', $report->id]) }}"><i class="fas fa-paperclip text-info opacity-75 me-2"></i> Lihat File</a></li>
                                        @endif
                                        <li><a class="dropdown-item rounded-3 py-2" href="{{ route('admin.reports.download', $report->id) }}"><i class="fas fa-download text-primary opacity-75 me-2"></i> Download PDF</a></li>
                                        <li><a class="dropdown-item rounded-3 py-2" href="{{ route('admin.reports.edit', $report->id) }}"><i class="fas fa-edit text-warning text-dark opacity-75 me-2"></i> Edit Laporan</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.reports.delete', $report->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item rounded-3 py-2 text-danger"><i class="fas fa-trash text-danger opacity-75 me-2"></i> Hapus</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-md-end">
                                {{-- Workflow Buttons --}}
                                <x-workflow-buttons :report="$report" :user="auth()->user()" :staffList="$staffList" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body py-5 text-center">
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 80px; height: 80px;">
                        <i class="fas fa-inbox fa-3x text-muted opacity-50"></i>
                    </div>
                    <h6 class="text-muted fw-bold">Tidak ada laporan saat ini</h6>
                    <p class="text-muted small mb-0">Laporan yang masuk dari masyarakat akan muncul di sini</p>
                </div>
            </div>
            @endforelse
            
            @if($reports->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $reports->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@push('modals')
<!-- View Report Modals -->
@foreach($reports as $report)
<div class="modal fade" id="viewReportModal{{ $report->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Detail Laporan <span class="text-muted ms-2 fs-6">#{{ $report->ticket_no }}</span>
                </h5>
                <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal" style="box-shadow: none;"></button>
            </div>
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-3 border-bottom">
                            <div class="d-flex align-items-center mb-3 mb-md-0">
                                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 56px; height: 56px; font-size: 1.5rem; font-weight: bold;">
                                    {{ substr($report->user ? $report->user->name : 'N', 0, 1) }}
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold text-dark">{{ $report->user ? $report->user->name : 'Pengguna Dihapus' }}</h5>
                                    <div class="text-muted small d-flex flex-wrap align-items-center gap-2">
                                        <span><i class="fas fa-envelope me-1"></i>{{ $report->user ? $report->user->email : '-' }}</span>
                                        <span class="d-none d-md-inline">•</span>
                                        <span><i class="fas fa-calendar-alt me-1"></i>{{ $report->created_at->translatedFormat('d F Y, H:i') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-md-end">
                                <span class="badge bg-{{ in_array($report->status, ['submitted', 'pending']) ? 'warning' : ($report->status == 'resolved' ? 'success' : 'info') }} bg-opacity-10 text-{{ in_array($report->status, ['submitted', 'pending']) ? 'warning' : ($report->status == 'resolved' ? 'success' : 'info') }} rounded-pill px-3 py-2 mb-2 border border-{{ in_array($report->status, ['submitted', 'pending']) ? 'warning' : ($report->status == 'resolved' ? 'success' : 'info') }}-subtle shadow-sm d-inline-flex align-items-center">
                                    <i class="fas fa-circle me-2" style="font-size: 0.4rem;"></i> {{ \App\Models\Report::statusLabel($report->status) }}
                                </span><br>
                                <span class="badge bg-{{ $report->priority == 'urgent' ? 'danger' : ($report->priority == 'high' ? 'warning' : ($report->priority == 'medium' ? 'info' : 'secondary')) }} bg-opacity-10 text-{{ $report->priority == 'urgent' ? 'danger' : ($report->priority == 'high' ? 'warning' : ($report->priority == 'medium' ? 'info' : 'secondary')) }} rounded-pill px-3 py-1 border border-{{ $report->priority == 'urgent' ? 'danger' : ($report->priority == 'high' ? 'warning' : ($report->priority == 'medium' ? 'info' : 'secondary')) }}-subtle">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Prioritas {{ \App\Models\Report::priorityLabel($report->priority) }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="fw-bold mb-3 text-dark">{{ $report->title }}</h4>
                            <div class="p-3 bg-light rounded-3 text-dark border" style="line-height: 1.6; white-space: pre-line;">
                                {{ $report->description }}
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white">
                                    <div class="text-muted small mb-2 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Kategori & Departemen</div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-success-subtle text-success rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fas fa-tag"></i></div>
                                        <span class="fw-bold text-dark">{{ $report->category }}</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info-subtle text-info rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fas fa-building"></i></div>
                                        <span class="fw-bold text-dark">{{ $report->department ? $report->department->name : 'Tidak ada departemen' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white">
                                    <div class="text-muted small mb-2 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Lokasi Kejadian</div>
                                    <div class="d-flex align-items-start">
                                        <div class="bg-danger-subtle text-danger rounded-circle p-2 me-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;"><i class="fas fa-map-marker-alt"></i></div>
                                        <span class="fw-bold text-dark mt-1">{{ $report->location ?? 'Tidak disebutkan' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($report->assignedUser)
                <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary-subtle bg-opacity-10 border border-primary-subtle">
                    <div class="card-body p-3 d-flex align-items-center flex-wrap gap-3">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="fas fa-user-tie text-primary fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="text-primary small fw-bold text-uppercase mb-1" style="letter-spacing: 0.5px;">Ditugaskan kepada (Staff)</div>
                            <h6 class="fw-bold text-dark mb-0">{{ $report->assignedUser->name }}</h6>
                        </div>
                        <div>
                            <a href="mailto:{{ $report->assignedUser->email }}" class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="fas fa-envelope me-2"></i>Hubungi Staff</a>
                        </div>
                    </div>
                </div>
                @endif
                
                @if($report->status === 'awaiting_info' && $report->info_request)
                <div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4 d-flex align-items-start">
                    <div class="bg-white text-warning rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Menunggu Data dari Pelapor</h6>
                        <p class="mb-0 text-dark">{{ $report->info_request }}</p>
                    </div>
                </div>
                @endif

                @if($report->rejection_reason)
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4 d-flex align-items-start">
                    <div class="bg-white text-danger rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Catatan Penolakan / Revisi</h6>
                        <p class="mb-0 text-dark">{{ $report->rejection_reason }}</p>
                    </div>
                </div>
                @endif

                @if($report->attachments && count($report->attachments) > 0)
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-dark d-flex align-items-center">
                            <i class="fas fa-paperclip me-2 text-primary"></i>Lampiran File <span class="badge bg-secondary ms-2 rounded-pill">{{ count($report->attachments) }}</span>
                        </h6>
                        <div class="row g-2 mb-3">
                            @foreach($report->attachments as $attachment)
                            <div class="col-sm-6 col-md-4">
                                <div class="border rounded-3 p-2 d-flex align-items-center bg-white shadow-sm h-100 transition-all hover-translate-y">
                                    <div class="bg-light rounded p-2 me-2">
                                        <i class="fas fa-file-alt text-primary fs-4"></i>
                                    </div>
                                    <div class="text-truncate small fw-bold text-dark" title="{{ basename($attachment) }}">
                                        {{ basename($attachment) }}
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <a href="{{ route('files.view', ['report', $report->id]) }}" class="btn btn-outline-primary w-100 rounded-pill fw-bold border-2">
                            <i class="fas fa-external-link-alt me-2"></i>Buka Viewer File
                        </a>
                    </div>
                </div>
                @endif
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-white">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border shadow-sm" data-bs-dismiss="modal">Tutup</button>
                @if(in_array($report->status, ['submitted']))
                    <a href="{{ route('admin.reports.edit', $report->id) }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-edit me-2"></i>Tindak Lanjut
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endpush

<style>
    .report-card {
        z-index: 1;
    }
    .report-card:hover, .report-card:focus-within {
        z-index: 1050 !important;
    }
    .btn-hover-elevate {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-hover-elevate:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
    }
    .transition-all {
        transition: all 0.2s ease;
    }
    .hover-translate-y:hover {
        transform: translateY(-2px);
        border-color: var(--bs-primary) !important;
    }
    .w-20px {
        width: 20px;
        text-align: center;
    }
    
    /* Fix transparent modal issue */
    .modal-content {
        background: #ffffff !important;
    }
    .dark .modal-content {
        background: #1e293b !important;
    }
    .modal-content .card {
        background: #ffffff !important;
    }
    .dark .modal-content .card {
        background: #0f172a !important;
    }
</style>
@endsection

