@extends('layouts.dashboard')
@section('title', 'Keluhan Saya')
@section('content')
@php
    $statusLabels = \App\Models\Report::STATUS_LABELS;
    $priorityLabels = \App\Models\Report::PRIORITY_LABELS;
@endphp
<div class="dh-workspace">
@include('administration.head.heading', ['heading' => 'Keluhan Saya', 'description' => 'Lihat status dan kelola semua keluhan yang telah Anda sampaikan.'])

<section class="dh-panel">
    <div class="dh-panel-heading mb-4">
        <div>
            <h2>Daftar Keluhan</h2>
        </div>
        <a class="dh-button" href="{{ route('citizen.complaints.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> Buat Keluhan</a>
    </div>
    
    <div class="dh-table-wrap">
        <table class="dh-table">
            <thead>
                <tr>
                    <th scope="col">Keluhan</th>
                    <th scope="col">Status & prioritas</th>
                    <th scope="col">Kategori & Departemen</th>
                    <th scope="col">Tanggal masuk</th>
                    <th scope="col">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $ticket)
                <tr>
                    <td>
                        <small class="dh-ticket-no">{{ $ticket->ticket_no }}</small>
                        <a class="dh-title-button d-inline-block" href="{{ route('citizen.complaints.show', $ticket->id) }}">{{ $ticket->title }}</a>
                    </td>
                    <td>
                        <span class="dh-status dh-status-{{ $ticket->status }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                        <small class="dh-priority dh-priority-{{ $ticket->priority }}"><i class="fas fa-circle" aria-hidden="true"></i> {{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</small>
                    </td>
                    <td>
                        <span class="dh-assignee">{{ $ticket->department?->name ?? 'Umum' }}</span>
                        <small class="dh-muted">{{ ucfirst($ticket->category) }}</small>
                    </td>
                    <td class="text-nowrap">
                        {{ $ticket->created_at->translatedFormat('d M Y') }}
                        <small class="dh-muted">{{ $ticket->created_at->format('H:i') }} WIB</small>
                    </td>
                    <td>
                        <a class="dh-button dh-button-outline" href="{{ route('citizen.complaints.show', $ticket->id) }}">Lihat detail <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="dh-empty">
                            <i class="fas fa-comment-dots" aria-hidden="true"></i>
                            <h3>Belum Ada Keluhan</h3>
                            <p>Anda belum membuat keluhan apapun.</p>
                            <a class="dh-button mt-2" href="{{ route('citizen.complaints.create') }}">Sampaikan keluhan pertama Anda</a>
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
@endsection
