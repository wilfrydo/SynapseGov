@extends('layouts.dashboard')

@section('title', 'Pengguna Admin')

@section('content')
@include('admin.heading')
<!-- Error Messages -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <strong>Error!</strong>
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Users Table -->
<div class="users-card">
    <div class="users-card-header">
        <i class="fas fa-list"></i>
        <h3>Daftar Pengguna ({{ $users->total() }} Total)</h3>
    </div>
    <div class="users-card-body">
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 20%;">Nama</th>
                            <th style="width: 20%;">Email</th>
                            <th style="width: 12%;">Role</th>
                            <th style="width: 18%;">Departemen</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 15%;">Tanggal Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr>
                            <td>
                                <span style="background: #f0f9ff; color: #0284c7; padding: 0.25rem 0.75rem; border-radius: 12px; font-weight: 600; font-size: 0.85rem;">
                                    #{{ $user->id }}
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem;">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-main);">{{ $user->name }}</div>
                                        <div style="font-size: 0.8rem; color: var(--text-subtle);">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">{{ $user->email }}</span>
                            </td>
                            <td>
                                @php
                                    $roleColors = [
                                        'admin' => ['bg' => '#fef2f2', 'color' => '#ef4444', 'label' => 'Admin'],
                                        'department_head' => ['bg' => '#fef3c7', 'color' => '#f59e0b', 'label' => 'Kepala Dept'],
                                        'staff' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'label' => 'Staff'],
                                        'citizen' => ['bg' => '#f0fdf4', 'color' => '#047857', 'label' => 'Warga']
                                    ];
                                    $roleInfo = $roleColors[$user->role] ?? ['bg' => '#f8f9fa', 'color' => '#666', 'label' => ucfirst($user->role)];
                                @endphp
                                <span style="background: {{ $roleInfo['bg'] }}; color: {{ $roleInfo['color'] }}; padding: 0.35rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                    {{ $roleInfo['label'] }}
                                </span>
                            </td>
                            <td>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">
                                    {{ $user->department ? $user->department->name : '-' }}
                                </span>
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span style="background: #f0fdf4; color: #047857; padding: 0.35rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                        Aktif
                                    </span>
                                @else
                                    <span style="background: #fef2f2; color: #ef4444; padding: 0.35rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                        Nonaktif
                                    </span>
                                @endif
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.toggle_status', $user->id) }}" method="POST" style="margin-top: 0.4rem;"
                                          onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan akun ini? Pengguna tidak akan bisa login.' : 'Aktifkan kembali akun ini?' }}')">
                                        @csrf
                                        <button type="submit" style="background: none; border: none; padding: 0; font-size: 0.8rem; font-weight: 600; cursor: pointer; color: {{ $user->is_active ? '#ef4444' : '#10b981' }};">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                <span style="color: var(--text-subtle); font-size: 0.9rem;">{{ $user->created_at ? $user->created_at->translatedFormat('d M Y') : '-' }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination - Kecil -->
            @if($users->hasPages())
                <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 1.5rem; flex-wrap: wrap;">
                    {{-- Previous Page Link --}}
                    @if ($users->onFirstPage())
                        <span style="color: #ccc; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #e9ecef; border-radius: 4px; cursor: not-allowed;">
                            ‹ Prev
                        </span>
                    @else
                        <a href="{{ $users->previousPageUrl() }}" style="color: #0284c7; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #e9ecef; border-radius: 4px; text-decoration: none; transition: all 0.2s;">
                            ‹ Prev
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($users->getUrlRange(1, $users->lastPage()) as $page => $url)
                        @if ($page == $users->currentPage())
                            <span style="background: #0284c7; color: white; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #0284c7; border-radius: 4px; font-weight: 600;">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" style="color: #0284c7; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #e9ecef; border-radius: 4px; text-decoration: none; transition: all 0.2s;">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}" style="color: #0284c7; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #e9ecef; border-radius: 4px; text-decoration: none; transition: all 0.2s;">
                            Next ›
                        </a>
                    @else
                        <span style="color: #ccc; font-size: 0.75rem; padding: 0.25rem 0.5rem; border: 1px solid #e9ecef; border-radius: 4px; cursor: not-allowed;">
                            Next ›
                        </span>
                    @endif
                </div>
            @endif
        @else
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>Belum ada pengguna terdaftar</p>
                <p style="font-size: 0.9rem; color: #bbb; margin-top: 0.5rem;">Mulai dengan menambahkan pengguna baru</p>
            </div>
        @endif
    </div>
</div>

<!-- View User Modals -->
@foreach($users as $user)
<div class="modal fade" id="viewUserModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengguna #{{ $user->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Pribadi</h6>
                        <p><strong>Nama:</strong> {{ $user->name }}</p>
                        <p><strong>Email:</strong> {{ $user->email }}</p>
                        <p><strong>Role:</strong> 
                            <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'department_head' ? 'warning' : ($user->role == 'staff' ? 'info' : 'secondary')) }}">
                                {{ ucfirst($user->role) }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Departemen</h6>
                        @if($user->department)
                        <p><strong>Departemen:</strong> {{ $user->department->name }}</p>
                        <p><strong>Kode:</strong> {{ $user->department->code }}</p>
                        <p><strong>Email Departemen:</strong> {{ $user->department->email }}</p>
                        @else
                        <p class="text-muted">Tidak terdaftar di departemen</p>
                        @endif
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Informasi Akun</h6>
                        <p><strong>Tanggal Daftar:</strong> {{ $user->created_at->translatedFormat('d F Y, H:i') }}</p>
                        <p><strong>Terakhir Update:</strong> {{ $user->updated_at->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

