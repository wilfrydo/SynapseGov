@extends('layouts.dashboard')

@section('title', 'Detail Laporan')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="page-header-modern d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-file-alt me-2"></i>Detail Laporan</h2>
            <p>Informasi lengkap laporan Anda</p>
        </div>
        <a href="{{ route('citizen.reports.index') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i>Kembali
        </a>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Report Info Card -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <i class="fas fa-info-circle"></i>
                    <h5>Informasi Laporan</h5>
                </div>
                <div class="card-body">
                    <!-- Ticket Number Badge -->
                    <div class="mb-3 pb-3" style="border-bottom: 1px solid var(--border);">
                        <span class="badge" style="background: var(--primary); font-size: 0.9rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-ticket-alt me-2"></i>{{ $report->ticket_no }}
                        </span>
                    </div>
                    
                    <!-- Title & Description -->
                    <h4 class="mb-3" style="font-weight: 700; color: var(--text);">{{ $report->title }}</h4>
                    <p class="mb-4" style="color: var(--muted); line-height: 1.7;">{{ $report->description }}</p>
                    
                    <!-- Details Grid -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-tag me-2"></i>Kategori</label>
                                <div class="detail-value">{{ ucfirst($report->category) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-flag me-2"></i>Prioritas</label>
                                <div class="detail-value">
                                    <span class="badge badge-priority-{{ $report->priority }}">
                                        {{ \App\Models\Report::priorityLabel($report->priority) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @if($report->location)
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-map-marker-alt me-2"></i>Lokasi</label>
                                <div class="detail-value">{{ $report->location }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-building me-2"></i>Departemen</label>
                                <div class="detail-value">{{ $report->department->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-clock me-2"></i>Dibuat</label>
                                <div class="detail-value">{{ $report->created_at->translatedFormat('d F Y, H:i') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-sync me-2"></i>Terakhir Update</label>
                                <div class="detail-value">{{ $report->updated_at->translatedFormat('d F Y, H:i') }}</div>
                            </div>
                        </div>
                        @if($report->assignedUser)
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-user-check me-2"></i>Ditugaskan ke</label>
                                <div class="detail-value">{{ $report->assignedUser->name }}</div>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="detail-item">
                                <label class="detail-label"><i class="fas fa-circle-notch me-2"></i>Status</label>
                                <div class="detail-value">
                                    <span class="badge badge-status-{{ $report->status }}">
                                        {{ \App\Models\Report::statusLabel($report->status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
            <!-- Awaiting Info Action Card (Data Kurang) -->
            @if($report->status === 'awaiting_info')
            <div class="card mb-4 border-warning shadow-sm">
                <div class="card-header bg-warning text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-circle fa-lg"></i>
                    <h5 class="mb-0 fw-bold">Data Kurang - Mohon Lengkapi Laporan Anda</h5>
                </div>
                <div class="card-body">
                    @if($report->rejection_reason)
                    <div class="alert alert-warning mb-3">
                        <strong>Catatan Admin / Data yang Diperlukan:</strong>
                        <p class="mb-0 mt-1">{{ $report->rejection_reason }}</p>
                    </div>
                    @endif
                    <form action="{{ route('workflow.reports.provide_info', $report->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="information" class="form-label fw-bold">Data Tambahan / Penjelasan:</label>
                            <textarea class="form-control" id="information" name="information" rows="4" placeholder="Tuliskan data tambahan yang diminta oleh admin di sini..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="citizen_attachments" class="form-label fw-bold">Unggah Dokumen / Foto Pendukung Tambahan (Opsional):</label>
                            <input type="file" class="form-control" id="citizen_attachments" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
                            <small class="text-muted">Maksimal 5MB per file (JPG, PNG, PDF, DOCX).</small>
                        </div>
                        <button type="submit" class="btn btn-warning text-dark fw-bold">
                            <i class="fas fa-paper-plane me-1"></i> Kirim Kelengkapan Data ke Admin
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Rejected Notice Card (Tidak Layak) -->
            @if($report->status === 'rejected')
            <div class="card mb-4 border-danger shadow-sm">
                <div class="card-header bg-danger text-white d-flex align-items-center gap-2">
                    <i class="fas fa-times-circle fa-lg"></i>
                    <h5 class="mb-0 fw-bold">Laporan Tidak Layak / Ditolak</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Laporan Anda telah diperiksa oleh Admin Utama dan dinyatakan tidak memenuhi kriteria untuk diproses lebih lanjut.</p>
                    @if($report->rejection_reason)
                    <div class="alert alert-danger mb-0">
                        <strong>Alasan Penolakan:</strong>
                        <p class="mb-0 mt-1">{{ $report->rejection_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Citizen Confirmation Card (Hasil Disetujui -> Resolved) -->
            @if($report->status === 'resolved')
            <div class="card mb-4 border-success shadow-sm">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="fas fa-clipboard-check fa-lg"></i>
                    <h5 class="mb-0 fw-bold">Konfirmasi Hasil Penanganan Laporan</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Petugas telah menyelesaikan penanganan laporan ini dan telah disetujui oleh Admin. Silakan konfirmasi apakah masalah Anda sudah benar-benar terselesaikan.</p>
                    
                    @if($report->final_notes || $report->completion_notes || $report->resolution_notes)
                    <div class="alert alert-info mb-3">
                        <strong>Catatan Hasil Penanganan:</strong>
                        <p class="mb-0 mt-1">{{ $report->final_notes ?? $report->completion_notes ?? $report->resolution_notes }}</p>
                    </div>
                    @endif

                    <div class="d-flex flex-wrap gap-2">
                        <!-- Selesai -> closed -->
                        <form action="{{ route('workflow.reports.citizen_close', $report->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success fw-bold" onclick="return confirm('Apakah Anda yakin laporan ini telah selesai dan dapat diarsipkan?')">
                                <i class="fas fa-check-circle me-1"></i> Ya, Masalah Selesai (Arsipkan)
                            </button>
                        </form>

                        <!-- Masalah Belum Selesai -> loops back to in_progress -->
                        <button type="button" class="btn btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#citizenReopenModal">
                            <i class="fas fa-undo me-1"></i> Masalah Belum Selesai
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Masalah Belum Selesai -->
            <div class="modal fade" id="citizenReopenModal" tabindex="-1" aria-labelledby="citizenReopenModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="citizenReopenModalLabel">
                                <i class="fas fa-undo text-warning me-2"></i>Laporan Masalah Belum Selesai
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('workflow.reports.reopen', $report->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Laporan akan dikembalikan ke status <strong>Sedang Dikerjakan (in_progress)</strong> dan ditugaskan kembali ke staf lapangan untuk ditindaklanjuti.
                                </div>
                                <div class="mb-3">
                                    <label for="reopen_reason" class="form-label fw-bold">Jelaskan kendala yang masih terjadi:</label>
                                    <textarea class="form-control" id="reopen_reason" name="reason" rows="4" placeholder="Jelaskan secara jelas mengapa masalah dianggap belum selesai atau apa yang masih perlu diperbaiki..." required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-warning text-dark fw-bold">
                                    <i class="fas fa-paper-plane me-1"></i>Kirim Tindak Lanjut
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Closed Notice Card (Laporan Diarsipkan) -->
            @if($report->status === 'closed')
            <div class="card mb-4 border-secondary shadow-sm">
                <div class="card-header bg-secondary text-white d-flex align-items-center gap-2">
                    <i class="fas fa-archive fa-lg"></i>
                    <h5 class="mb-0 fw-bold">Laporan Selesai & Diarsipkan</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">Laporan ini telah dikonfirmasi selesai dan resmi diarsipkan. Terima kasih telah berpartisipasi dalam perbaikan layanan publik.</p>
                </div>
            </div>
            @endif

            <!-- Resolution Notes -->
            @if($report->resolution_notes)
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i>
                    <h5 class="mb-0" style="font-weight: 600;">Catatan Resolusi</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-0" style="border-left: 4px solid var(--success);">
                        <p class="mb-2">{{ $report->resolution_notes }}</p>
                        @if($report->resolved_at)
                        <small class="text-muted">
                            <i class="fas fa-calendar-check me-1"></i>
                            Diselesaikan pada: {{ $report->resolved_at->translatedFormat('d F Y, H:i') }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Attachments -->
            @if($report->attachments && is_array($report->attachments) && count($report->attachments) > 0)
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-paperclip" style="color: var(--primary);"></i>
                    <h5 class="mb-0" style="font-weight: 600;">Lampiran</h5>
                </div>
                <div class="card-body">
                    <div class="attachment-list">
                        @foreach($report->attachments as $file)
                        <div class="attachment-item">
                            <div class="attachment-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="attachment-info">
                                <div class="attachment-name">{{ basename($file) }}</div>
                                <small class="text-muted">File lampiran</small>
                            </div>
                            <a class="btn btn-sm btn-primary" href="{{ route('files.download', ['type' => 'report', 'id' => $report->id, 'filename' => basename($file)]) }}" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            <!-- Timeline -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-history" style="color: var(--primary);"></i>
                    <h5 class="mb-0" style="font-weight: 600;">Status Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="modern-timeline">
                        <div class="timeline-step completed">
                            <div class="timeline-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="timeline-details">
                                <h6>Laporan Dibuat</h6>
                                <small>{{ $report->created_at->translatedFormat('d F Y, H:i') }}</small>
                            </div>
                        </div>
                        
                        @if($report->status != 'pending')
                        <div class="timeline-step completed">
                            <div class="timeline-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="timeline-details">
                                <h6>Laporan Diproses</h6>
                                <small>{{ $report->updated_at->translatedFormat('d F Y, H:i') }}</small>
                            </div>
                        </div>
                        @endif
                        
                        @if($report->status == 'resolved')
                        <div class="timeline-step completed">
                            <div class="timeline-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="timeline-details">
                                <h6>Laporan Diselesaikan</h6>
                                <small>{{ $report->resolved_at ? $report->resolved_at->translatedFormat('d F Y, H:i') : $report->updated_at->translatedFormat('d F Y, H:i') }}</small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Department Info -->
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="fas fa-building" style="color: var(--primary);"></i>
                    <h5 class="mb-0" style="font-weight: 600;">Informasi Departemen</h5>
                </div>
                <div class="card-body">
                    <h5 class="mb-2" style="color: var(--text);">{{ $report->department->name }}</h5>
                    <p class="text-muted mb-3">{{ $report->department->description }}</p>
                    
                    <div class="dept-info-grid">
                        <div class="dept-info-item">
                            <i class="fas fa-code me-2"></i>
                            <span>{{ $report->department->code }}</span>
                        </div>
                        <div class="dept-info-item">
                            <i class="fas fa-envelope me-2"></i>
                            <span>{{ $report->department->email }}</span>
                        </div>
                        <div class="dept-info-item">
                            <i class="fas fa-phone me-2"></i>
                            <span>{{ $report->department->phone }}</span>
                        </div>
                        @if($report->department->address)
                        <div class="dept-info-item">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span>{{ $report->department->address }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Detail Items */
.detail-item {
    padding: 0.75rem;
    background: rgba(37, 99, 235, 0.03);
    border-radius: 8px;
    border: 1px solid var(--border);
}
.detail-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
    display: block;
}
.detail-value {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text);
}

/* Priority Badges */
.badge-priority-urgent {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-priority-high {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: #111827;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-priority-medium {
    background: linear-gradient(135deg, #0891b2 0%, #22d3ee 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-priority-low {
    background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}

/* Status Badges */
.badge-status-pending, .badge-status-submitted, .badge-status-awaiting_info {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    color: #111827;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-status-verified, .badge-status-in_progress, .badge-status-assigned, .badge-status-awaiting_admin_approval {
    background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-status-resolved, .badge-status-closed {
    background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}
.badge-status-rejected, .badge-status-needs_revision {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-weight: 600;
}

/* Attachments */
.attachment-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.attachment-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(37, 99, 235, 0.03);
    border: 1px solid var(--border);
    border-radius: 10px;
    transition: all 0.2s ease;
}
.attachment-item:hover {
    background: rgba(37, 99, 235, 0.08);
    transform: translateX(4px);
}
.attachment-icon {
    width: 40px;
    height: 40px;
    background: var(--primary);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.attachment-info {
    flex: 1;
    min-width: 0;
}
.attachment-name {
    font-weight: 600;
    color: var(--text);
    font-size: 0.9rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Modern Timeline */
.modern-timeline {
    position: relative;
    padding-left: 2rem;
}
.modern-timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--primary) 0%, var(--border) 100%);
}
.timeline-step {
    position: relative;
    padding-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
}
.timeline-step:last-child {
    padding-bottom: 0;
}
.timeline-icon {
    position: absolute;
    left: -2rem;
    width: 32px;
    height: 32px;
    background: var(--card);
    border: 3px solid var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 0.9rem;
    z-index: 1;
}
.timeline-step.completed .timeline-icon {
    background: var(--primary);
    color: white;
}
.timeline-details h6 {
    font-weight: 600;
    color: var(--text);
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}
.timeline-details small {
    color: var(--muted);
    font-size: 0.85rem;
}

/* Department Info */
.dept-info-grid {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.dept-info-item {
    display: flex;
    align-items: center;
    padding: 0.5rem;
    background: rgba(37, 99, 235, 0.03);
    border-radius: 6px;
    color: var(--text);
    font-size: 0.9rem;
}
.dept-info-item i {
    color: var(--primary);
    width: 20px;
}
</style>
@endsection

