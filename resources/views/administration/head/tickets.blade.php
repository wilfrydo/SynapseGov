@extends('layouts.dashboard')
@section('title', $isReport ? 'Laporan Departemen' : 'Keluhan Departemen')
@section('content')
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
    $priorityLabels = \App\Models\Report::PRIORITY_LABELS;
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => $isReport ? 'Laporan masyarakat' : 'Keluhan & aspirasi', 'description' => $isReport ? 'Pantau setiap laporan, tentukan penanggung jawab, dan tindak lanjuti progresnya.' : 'Dengarkan kebutuhan masyarakat dan koordinasikan penanganannya bersama tim.'])
<div class="dh-tabs" aria-label="Filter status">
<a class="{{ !request('status') && !request('overdue') ? 'is-active' : '' }}" href="{{ route('administration.'.$type) }}">Semua <span>{{ $statusCounts->sum() }}</span></a>
@foreach(($isReport ? ['verified','in_progress','resolved'] : ['submitted','investigating','resolved']) as $status)
<a class="{{ request('status') === $status ? 'is-active' : '' }}" href="{{ route('administration.'.$type, ['status' => $status]) }}">{{ $statusLabels[$status] }} <span>{{ $statusCounts->get($status, 0) }}</span></a>
@endforeach
</div>
<section class="dh-panel">
<form class="dh-filters" method="GET" action="{{ route('administration.'.$type) }}">
<div class="dh-search"><label for="ticket-search">Cari {{ $isReport ? 'laporan' : 'keluhan' }}</label><div><i class="fas fa-search" aria-hidden="true"></i><input id="ticket-search" name="q" value="{{ request('q') }}" maxlength="150" placeholder="Judul, nomor tiket, atau nama pelapor"></div></div>
<div><label for="ticket-status">Status</label><select id="ticket-status" name="status" class="form-select"><option value="">Semua status</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
<div><label for="ticket-priority">Prioritas</label><select id="ticket-priority" name="priority" class="form-select"><option value="">Semua prioritas</option>@foreach($priorityLabels as $value => $label)<option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>@endforeach</select></div>
@if(request('overdue'))<input type="hidden" name="overdue" value="1">@endif
<button class="dh-button" type="submit">Terapkan</button>
@if(request()->hasAny(['q','status','priority','overdue']))<a class="dh-reset" href="{{ route('administration.'.$type) }}">Reset</a>@endif
</form>
<div class="dh-results"><span>{{ number_format($tickets->total()) }} {{ $isReport ? 'laporan' : 'keluhan' }} ditemukan @if(request('overdue')) · Melewati SLA @endif</span><span>Terbaru lebih dahulu</span></div>
<div class="dh-table-wrap"><table class="dh-table"><thead><tr><th scope="col">{{ $isReport ? 'Laporan' : 'Keluhan' }}</th><th scope="col">Status & prioritas</th><th scope="col">Penanggung jawab</th><th scope="col">Tanggal masuk</th><th scope="col">Tindakan</th></tr></thead><tbody>
@forelse($tickets as $ticket)
<tr><td><small class="dh-ticket-no">{{ $ticket->ticket_no }}</small><button class="dh-title-button" data-bs-toggle="modal" data-bs-target="#ticketDetail{{ $ticket->id }}">{{ $ticket->title }}</button><span class="dh-muted">{{ $ticket->user?->name ?? 'Pengguna dihapus' }} · {{ $ticket->category }}</span></td><td><span class="dh-status dh-status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span><small class="dh-priority dh-priority-{{ $ticket->priority }}"><i class="fas fa-circle" aria-hidden="true"></i> {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</small></td><td><span class="dh-assignee">{{ $ticket->assignedUser?->name ?? 'Belum ditugaskan' }}</span>@if($ticket->sla_due_at && !in_array($ticket->status, ['resolved','closed','rejected']))<small class="{{ $ticket->sla_due_at->isPast() ? 'text-danger' : 'dh-muted' }}">SLA {{ $ticket->sla_due_at->translatedFormat('d M, H:i') }}</small>@endif</td><td class="text-nowrap">{{ $ticket->created_at->translatedFormat('d M Y') }}<small class="dh-muted">{{ $ticket->created_at->format('H:i') }} WIB</small></td><td><div class="d-flex align-items-center gap-2 flex-wrap">@if($isReport)<x-workflow-buttons :report="$ticket" :user="auth()->user()" :staff-list="$staffList" mode="buttons" />@endif<button class="dh-button dh-button-outline" data-bs-toggle="modal" data-bs-target="#ticketDetail{{ $ticket->id }}">Lihat detail <i class="fas fa-arrow-right" aria-hidden="true"></i></button></div></td></tr>
@empty
<tr><td colspan="5"><div class="dh-empty"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><h3>{{ request()->hasAny(['q','status','priority','overdue']) ? 'Tidak ada hasil yang cocok' : 'Belum ada data' }}</h3><p>Coba ubah filter atau periksa kembali nanti.</p><a href="{{ route('administration.'.$type) }}">Tampilkan semua {{ $isReport ? 'laporan' : 'keluhan' }}</a></div></td></tr>
@endforelse
</tbody></table></div>
<div class="dh-pagination">{{ $tickets->links('pagination::bootstrap-5') }}</div>
</section></div>
@foreach($tickets as $ticket)
@push('modals')
<div class="modal fade dh-detail" id="ticketDetail{{ $ticket->id }}" tabindex="-1" aria-labelledby="ticketTitle{{ $ticket->id }}" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
<div class="modal-header"><div><small class="dh-ticket-no">{{ $ticket->ticket_no }}</small><h2 class="modal-title fs-5" id="ticketTitle{{ $ticket->id }}">{{ $ticket->title }}</h2></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
<div class="modal-body"><div class="d-flex gap-2 mb-3"><span class="dh-status dh-status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span><span class="dh-status">Prioritas {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span></div>
@if($isReport)
<div class="dh-action-box mb-4" style="background: rgba(159, 36, 52, 0.08); border: 2px solid rgba(159, 36, 52, 0.35); border-radius: 12px; padding: 16px 20px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h3 class="fs-6 mb-1 text-main font-weight-bold" style="font-size: 0.95rem;"><i class="fas fa-bolt text-danger me-2"></i>Tindak Lanjut Penanganan</h3>
            <p class="dh-muted mb-0" style="font-size: 0.8rem;">Aksi penanganan untuk status: <strong class="badge bg-secondary text-uppercase">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</strong></p>
        </div>
        <div>
            <x-workflow-buttons :report="$ticket" :user="auth()->user()" :staff-list="$staffList" mode="buttons" />
        </div>
    </div>
</div>
@endif
<div class="dh-detail-grid"><div><small>PELAPOR</small><p>{{ $ticket->user?->name ?? 'Pengguna dihapus' }}</p></div><div><small>PENANGGUNG JAWAB</small><p>{{ $ticket->assignedUser?->name ?? 'Belum ditugaskan' }}</p></div><div><small>LOKASI</small><p>{{ $ticket->location ?: 'Tidak disebutkan' }}</p></div><div><small>KATEGORI</small><p>{{ $ticket->category }}</p></div></div>
<h3 class="fs-6">Deskripsi {{ $isReport ? 'laporan' : 'keluhan' }}</h3><p class="dh-description">{{ $ticket->description }}</p>
@if($ticket->completion_notes || $ticket->resolution_notes)<h3 class="fs-6">Catatan penanganan</h3><p class="dh-description">{{ $ticket->completion_notes ?: $ticket->resolution_notes }}</p>@endif
<div class="d-flex gap-2 flex-wrap mb-4">@if($ticket->attachments)<a class="dh-button dh-button-outline" href="{{ route('files.view', [$isReport ? 'report' : 'complaint', $ticket->id]) }}"><i class="fas fa-paperclip" aria-hidden="true"></i> Lihat {{ count($ticket->attachments) }} lampiran</a>@endif
@if($isReport)<a class="dh-button dh-button-outline" href="{{ route('reports.download_pdf', $ticket->id) }}"><i class="fas fa-download" aria-hidden="true"></i> Unduh PDF</a>@endif</div>
@if($isReport)
<div class="dh-action-box"><h3 class="fs-6">Tindak lanjut</h3><p class="dh-muted">Tindakan yang tersedia menyesuaikan status laporan.</p><x-workflow-buttons :report="$ticket" :user="auth()->user()" :staff-list="$staffList" mode="buttons" /></div>
@elseif(in_array($ticket->status, ['submitted', 'pending', 'investigating', 'in_progress']))
<div class="dh-action-box mb-3">
    <h3 class="fs-6 mb-2">Tugaskan kepada staf</h3>
    <form class="mb-3" action="{{ route('administration.complaints.assign', $ticket->id) }}" method="POST">
        @csrf
        <div class="d-flex gap-2">
            <select class="form-select" name="assigned_to" id="complaintStaff{{ $ticket->id }}" required>
                <option value="">Pilih staf aktif</option>
                @foreach($staffList as $member)
                    <option value="{{ $member->id }}" @selected($ticket->assigned_to == $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
            <button class="dh-button" style="white-space: nowrap;" @disabled($staffList->isEmpty())>Simpan</button>
        </div>
    </form>
    @if($ticket->canBeResolved())
    <form action="{{ route('administration.complaints.resolve', $ticket->id) }}" method="POST">
        @csrf
        <label for="complaintResolution{{ $ticket->id }}" class="form-label font-weight-bold">Tandai Selesai</label>
        <textarea class="form-control mb-2" id="complaintResolution{{ $ticket->id }}" name="resolution_notes" rows="2" placeholder="Catatan hasil penanganan keluhan masyarakat..." required></textarea>
        <button class="dh-button text-success" style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981;"><i class="fas fa-check-circle me-1"></i> Selesaikan Keluhan</button>
    </form>
    @else
    <p class="dh-muted mb-0">Tugaskan keluhan ke staf terlebih dahulu. Keluhan dapat diselesaikan setelah masuk tahap investigasi.</p>
    @endif
</div>
@endif
</div><div class="modal-footer d-flex justify-content-between align-items-center flex-wrap gap-2"><div>@if($isReport)<x-workflow-buttons :report="$ticket" :user="auth()->user()" :staff-list="$staffList" mode="buttons" />@endif</div><button type="button" class="dh-button dh-button-outline" data-bs-dismiss="modal">Tutup detail</button></div>
</div></div></div>
@endpush
@endforeach
@endsection
