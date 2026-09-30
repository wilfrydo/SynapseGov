@extends('layouts.dashboard')
@section('title', 'Keluhan Masyarakat')
@section('content')
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
    $priorityLabels = \App\Models\Report::PRIORITY_LABELS;
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Keluhan & Aspirasi', 'description' => 'Dengarkan kebutuhan masyarakat dan koordinasikan penanganannya.'])

<section class="dh-panel">
    <form class="dh-filters" method="GET" action="{{ route('administration.complaints') }}">
        <div class="dh-search">
            <label for="ticket-search">Cari keluhan</label>
            <div>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="ticket-search" name="q" value="{{ request('q') }}" maxlength="150" placeholder="Judul, nomor tiket, atau nama pelapor">
            </div>
        </div>
        <div>
            <label for="ticket-status">Status</label>
            <select id="ticket-status" name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="ticket-priority">Prioritas</label>
            <select id="ticket-priority" name="priority" class="form-select">
                <option value="">Semua prioritas</option>
                @foreach($priorityLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="dh-button" type="submit">Terapkan</button>
        @if(request()->hasAny(['q','status','priority']))
            <a class="dh-reset" href="{{ route('administration.complaints') }}">Reset</a>
        @endif
    </form>
    
    <div class="dh-results">
        <span>{{ number_format($complaints->total()) }} keluhan ditemukan</span>
        <span>Terbaru lebih dahulu</span>
    </div>
    
    <div class="dh-table-wrap">
        <table class="dh-table">
            <thead>
                <tr>
                    <th scope="col">Keluhan</th>
                    <th scope="col">Status & prioritas</th>
                    <th scope="col">Penanggung jawab</th>
                    <th scope="col">Tanggal masuk</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $ticket)
                <tr>
                    <td>
                        <small class="dh-ticket-no">{{ $ticket->ticket_no }}</small>
                        <button class="dh-title-button" data-bs-toggle="modal" data-bs-target="#ticketDetail{{ $ticket->id }}">{{ $ticket->title }}</button>
                        <span class="dh-muted">{{ $ticket->user?->name ?? 'Pengguna dihapus' }} · {{ $ticket->category }}</span>
                    </td>
                    <td>
                        <span class="dh-status dh-status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                        <small class="dh-priority dh-priority-{{ $ticket->priority }}"><i class="fas fa-circle" aria-hidden="true"></i> {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</small>
                    </td>
                    <td>
                        <span class="dh-assignee">{{ $ticket->assignedUser?->name ?? 'Belum ditugaskan' }}</span>
                        @if($ticket->sla_due_at && !in_array($ticket->status, ['resolved','closed','rejected']))
                            <small class="{{ $ticket->sla_due_at->isPast() ? 'text-danger' : 'dh-muted' }}">SLA {{ $ticket->sla_due_at->translatedFormat('d M, H:i') }}</small>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        {{ $ticket->created_at->translatedFormat('d M Y') }}
                        <small class="dh-muted">{{ $ticket->created_at->format('H:i') }} WIB</small>
                    </td>
                    <td>
                        <button class="dh-button dh-button-outline" data-bs-toggle="modal" data-bs-target="#ticketDetail{{ $ticket->id }}">Lihat detail <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="dh-empty">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                            <h3>{{ request()->hasAny(['q','status','priority']) ? 'Tidak ada hasil yang cocok' : 'Belum ada data' }}</h3>
                            <p>Coba ubah filter atau periksa kembali nanti.</p>
                            <a href="{{ route('administration.complaints') }}">Tampilkan semua keluhan</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="dh-pagination">
        {{ $complaints->links('pagination::bootstrap-5') }}
    </div>
</section>
</div>

@foreach($complaints as $ticket)
@push('modals')
<div class="modal fade dh-detail" id="ticketDetail{{ $ticket->id }}" tabindex="-1" aria-labelledby="ticketTitle{{ $ticket->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <small class="dh-ticket-no">{{ $ticket->ticket_no }}</small>
                    <h2 class="modal-title fs-5" id="ticketTitle{{ $ticket->id }}">{{ $ticket->title }}</h2>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-2 mb-4">
                    <span class="dh-status dh-status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                    <span class="dh-status">Prioritas {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span>
                </div>
                <div class="dh-detail-grid">
                    <div><small>PELAPOR</small><p>{{ $ticket->user?->name ?? 'Pengguna dihapus' }}</p></div>
                    <div><small>PENANGGUNG JAWAB</small><p>{{ $ticket->assignedUser?->name ?? 'Belum ditugaskan' }}</p></div>
                    <div><small>LOKASI</small><p>{{ $ticket->location ?: 'Tidak disebutkan' }}</p></div>
                    <div><small>KATEGORI</small><p>{{ $ticket->category }}</p></div>
                </div>
                
                <h3 class="fs-6">Deskripsi keluhan</h3>
                <p class="dh-description">{{ $ticket->description }}</p>
                
                @if($ticket->completion_notes || $ticket->resolution_notes)
                    <h3 class="fs-6">Catatan penanganan</h3>
                    <p class="dh-description">{{ $ticket->completion_notes ?: $ticket->resolution_notes }}</p>
                @endif
                
                <div class="d-flex gap-2 flex-wrap mb-4">
                    @if($ticket->attachments)
                        <a class="dh-button dh-button-outline" href="{{ route('files.view', ['complaint', $ticket->id]) }}"><i class="fas fa-paperclip" aria-hidden="true"></i> Lihat {{ count($ticket->attachments) }} lampiran</a>
                    @endif
                </div>
                
                @if(in_array($ticket->status, ['submitted','pending']))
                <form class="dh-action-box" action="{{ route('administration.complaints.assign', $ticket->id) }}" method="POST">
                    @csrf
                    <h3 class="fs-6">Tugaskan kepada staf</h3>
                    <label for="complaintStaff{{ $ticket->id }}" class="form-label">Penanggung jawab</label>
                    <select class="form-select mb-3" name="assigned_to" id="complaintStaff{{ $ticket->id }}" required>
                        <option value="">Pilih staf aktif</option>
                        @foreach($staffList as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @if($staffList->isEmpty())
                        <p class="dh-muted">Belum ada staf aktif. Hubungi administrator untuk menambahkan staf.</p>
                    @endif
                    <button class="dh-button" @disabled($staffList->isEmpty())>Simpan penugasan</button>
                </form>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="dh-button dh-button-outline" data-bs-dismiss="modal">Tutup detail</button>
            </div>
        </div>
    </div>
</div>
@endpush
@endforeach
@endsection
