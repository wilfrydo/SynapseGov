@extends('layouts.dashboard')

@section('title', 'Manajemen Departemen')

@section('content')
@include('admin.heading')
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle me-2"></i><strong>Terjadi kesalahan input:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Departments Table -->
<div class="card border-0 shadow-sm rounded-4 mb-4" style="overflow: visible !important;">
    <div class="card-header bg-white py-4 px-4 d-flex justify-content-between align-items-center border-bottom-0 rounded-top-4">
        <div class="d-flex align-items-center gap-2">
            <h5 class="m-0 fw-bold text-dark"><i class="fas fa-list me-2 text-muted"></i>Daftar Departemen</h5>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-semibold">
                {{ $departments->where('is_active', true)->count() }} Aktif
            </span>
            <span class="badge bg-light text-dark border rounded-pill px-3 py-2 fw-semibold">
                {{ $departments->count() }} Total
            </span>
        </div>
    </div>
    <div class="card-body p-0 bg-light rounded-bottom-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 8px; padding: 0 1rem;">
                <thead class="bg-light">
                    <tr>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px; width: 60px;">ID</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Departemen</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Kepala</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px;">Kontak</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px; text-align: center;">Status</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0" style="font-size: 0.75rem; letter-spacing: 0.5px; text-align: center;">Statistik</th>
                        <th class="text-uppercase text-secondary fw-semibold border-0 text-end" style="font-size: 0.75rem; letter-spacing: 0.5px; min-width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr class="bg-white shadow-sm" style="border-radius: 12px; transition: all 0.2s ease;">
                        <td class="border-0 rounded-start-3 text-muted fw-bold ps-3">#{{ $department->id }}</td>
                        <td class="border-0">
                            <div class="d-flex align-items-center gap-3 py-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; font-weight: bold;">
                                    {{ substr($department->name, 0, 1) }}
                                </div>
                                <div>
                                    <strong class="text-dark d-block mb-1">{{ $department->name }}</strong>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-light text-secondary border border-secondary-subtle" style="font-family: monospace;">{{ $department->code }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="border-0">
                            @if($department->head)
                                <div class="fw-semibold text-dark"><i class="fas fa-user-tie text-primary opacity-50 me-2"></i>{{ $department->head->name }}</div>
                                <div class="text-muted small ms-4">{{ $department->head->email }}</div>
                            @else
                                <span class="text-muted fst-italic small"><i class="fas fa-user-slash me-2 opacity-50"></i>Belum ditunjuk</span>
                            @endif
                        </td>
                        <td class="border-0">
                            @if($department->email)
                                <div class="small text-secondary mb-1"><i class="fas fa-envelope text-primary opacity-50 me-2"></i>{{ $department->email }}</div>
                            @endif
                            @if($department->phone)
                                <div class="small text-secondary"><i class="fas fa-phone text-primary opacity-50 me-2"></i>{{ $department->phone }}</div>
                            @endif
                            @if(!$department->email && !$department->phone)
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="border-0 text-center">
                            <form action="{{ route('admin.departments.toggle_status', $department->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent" title="Klik untuk mengubah status">
                                    <span class="badge bg-{{ $department->is_active ? 'success' : 'danger' }} bg-opacity-10 text-{{ $department->is_active ? 'success' : 'danger' }} border border-{{ $department->is_active ? 'success' : 'danger' }}-subtle rounded-pill px-3 py-2 cursor-pointer transition-all hover-translate-y">
                                        <i class="fas fa-{{ $department->is_active ? 'check-circle' : 'times-circle' }} me-1"></i> {{ $department->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </button>
                            </form>
                        </td>
                        <td class="border-0 text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <span class="badge bg-light text-secondary border shadow-sm" title="Total Staf"><i class="fas fa-users text-info opacity-75 me-1"></i>{{ $department->users_count }}</span>
                                <span class="badge bg-light text-secondary border shadow-sm" title="Laporan Masuk"><i class="fas fa-file-alt text-primary opacity-75 me-1"></i>{{ $department->reports_count }}</span>
                                <span class="badge bg-light text-secondary border shadow-sm" title="Keluhan Masuk"><i class="fas fa-exclamation-triangle text-warning opacity-75 me-1"></i>{{ $department->complaints_count }}</span>
                            </div>
                        </td>
                        <td class="border-0 rounded-end-3 text-end pe-3">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light text-primary border shadow-sm btn-hover-elevate rounded-circle" data-bs-toggle="modal" data-bs-target="#viewDepartmentModal{{ $department->id }}" title="Detail" style="width: 32px; height: 32px;">
                                    <i class="fas fa-eye opacity-75"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-warning border shadow-sm btn-hover-elevate rounded-circle" data-bs-toggle="modal" data-bs-target="#editDepartmentModal{{ $department->id }}" title="Edit" style="width: 32px; height: 32px;">
                                    <i class="fas fa-edit opacity-75 text-dark"></i>
                                </button>
                                <form action="{{ route('admin.departments.destroy', $department->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus departemen {{ $department->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger border shadow-sm btn-hover-elevate rounded-circle" title="Hapus" style="width: 32px; height: 32px;">
                                        <i class="fas fa-trash-alt opacity-75"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted bg-white rounded-3 shadow-sm">
                            <div class="d-flex flex-column align-items-center">
                                <i class="fas fa-sitemap fa-3x mb-3 text-secondary opacity-50"></i>
                                <h5>Belum Ada Departemen</h5>
                                <p class="text-muted mb-0">Silakan tambahkan departemen baru melalui tombol di atas.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Tambah Departemen -->
<div class="modal fade" id="createDepartmentModal" tabindex="-1" aria-labelledby="createDepartmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.departments.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createDepartmentModalLabel"><i class="fas fa-plus-circle me-2"></i>Tambah Departemen Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="create_name" class="form-label fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="create_name" name="name" required placeholder="Contoh: Dinas Kesehatan">
                        </div>
                        <div class="col-md-4">
                            <label for="create_code" class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="create_code" name="code" required maxlength="20" placeholder="Contoh: DINKES">
                        </div>
                        <div class="col-md-6">
                            <label for="create_email" class="form-label fw-semibold">Email Departemen</label>
                            <input type="email" class="form-control" id="create_email" name="email" placeholder="dinkes@pemerintah.go.id">
                        </div>
                        <div class="col-md-6">
                            <label for="create_phone" class="form-label fw-semibold">Nomor Telepon</label>
                            <input type="text" class="form-control" id="create_phone" name="phone" placeholder="021-12345678">
                        </div>
                        <div class="col-md-12">
                            <label for="create_head_id" class="form-label fw-semibold">Kepala Departemen</label>
                            <select class="form-select" id="create_head_id" name="head_id">
                                <option value="">-- Belum Ditentukan --</option>
                                @if(isset($potentialHeads))
                                    @foreach($potentialHeads as $potentialHead)
                                        @continue($potentialHead->department_id !== null)
                                        <option value="{{ $potentialHead->id }}">{{ $potentialHead->name }} ({{ ucfirst($potentialHead->role) }})</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="create_address" class="form-label fw-semibold">Alamat Kantor</label>
                            <textarea class="form-control" id="create_address" name="address" rows="2" placeholder="Jl. Merdeka No. 1..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="create_description" class="form-label fw-semibold">Deskripsi / Tugas Fungsi</label>
                            <textarea class="form-control" id="create_description" name="description" rows="3" placeholder="Uraian tanggung jawab departemen..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label fw-semibold" for="create_is_active">Status Aktif (Departemen siap menerima laporan & keluhan)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Departemen</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modals: Detail & Edit Departemen -->
@foreach($departments as $department)
<!-- Detail Modal -->
<div class="modal fade" id="viewDepartmentModal{{ $department->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Detail Departemen: {{ $department->name }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Nama Departemen</div>
                            <div class="fw-bold fs-5 text-dark">{{ $department->name }}</div>
                            <div class="badge bg-secondary mt-1">Kode: {{ $department->code }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Status Operasional</div>
                            <div class="mt-1">
                                <span class="badge bg-{{ $department->is_active ? 'success' : 'danger' }} fs-6">
                                    {{ $department->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </div>
                            <div class="text-muted small mt-2">Kepala Departemen: <strong>{{ $department->head ? $department->head->name : 'Belum Ada' }}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong><i class="fas fa-envelope text-primary me-2"></i>Email:</strong> {{ $department->email ?: '-' }}</p>
                        <p class="mb-1"><strong><i class="fas fa-phone text-primary me-2"></i>Telepon:</strong> {{ $department->phone ?: '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong><i class="fas fa-map-marker-alt text-primary me-2"></i>Alamat:</strong> {{ $department->address ?: '-' }}</p>
                    </div>
                    <div class="col-12">
                        <div class="border-top pt-2 mt-2">
                            <strong>Deskripsi:</strong>
                            <p class="text-muted mb-0">{{ $department->description ?: 'Tidak ada deskripsi.' }}</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="row text-center mt-2 g-2">
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <div class="fs-4 fw-bold text-info">{{ $department->users_count }}</div>
                                    <div class="small text-muted">Total Staf</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <div class="fs-4 fw-bold text-primary">{{ $department->reports_count }}</div>
                                    <div class="small text-muted">Laporan Masuk</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 border rounded bg-white">
                                    <div class="fs-4 fw-bold text-warning">{{ $department->complaints_count }}</div>
                                    <div class="small text-muted">Keluhan Masuk</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDepartmentModal{{ $department->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Departemen: {{ $department->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Departemen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $department->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" name="code" value="{{ old('code', $department->code) }}" required maxlength="20">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Departemen</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $department->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nomor Telepon</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $department->phone) }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Kepala Departemen</label>
                            <select class="form-select" name="head_id">
                                <option value="">-- Belum Ditentukan --</option>
                                @if(isset($potentialHeads))
                                    @foreach($potentialHeads as $potentialHead)
                                        @continue($potentialHead->department_id !== null && (int) $potentialHead->department_id !== (int) $department->id)
                                        <option value="{{ $potentialHead->id }}" {{ $department->head_id == $potentialHead->id ? 'selected' : '' }}>
                                            {{ $potentialHead->name }} ({{ ucfirst($potentialHead->role) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Alamat Kantor</label>
                            <textarea class="form-control" name="address" rows="2">{{ old('address', $department->address) }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3">{{ old('description', $department->description) }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active_{{ $department->id }}" value="1" {{ $department->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="edit_is_active_{{ $department->id }}">Status Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

